#from flask import current_app as app
from hashlib import md5
#from app import constants
import clang.cindex
import os
import gzip
import json
from random import choice


def get_binary_operation(node):
    children = list(node.get_children())
    lo = children[0].extent.end.column
    hi = children[1].extent.start.column
    for token in node.get_tokens():
        if lo <= token.extent.start.column <= hi and lo <= token.extent.end.column <= hi:
            return token.spelling, token.extent.start.column, token.extent.end.column


def get_unary_operation(node):
    tokens = list(node.get_tokens())
    for token in tokens:
        if token.spelling == '--' or token.spelling == '++' or token.spelling == '!':
            return token.spelling


class ComplexityCalculator:

    def __init__(self, ast, row_counts, ignore_filename, scalars):
        self.ast = ast
        self.row_counts = row_counts
        self.ignore_filename = ignore_filename
        self.scalars = {
            x.description: x.scalar for x in scalars
        }


    def _dfs(self, node):
        result = 0
        if str(node.extent.start.file) != self.ignore_filename and node.extent.start.file is not None:
            return result

        if node.kind == clang.cindex.CursorKind.BINARY_OPERATOR:
            operation = get_binary_operation(node)[0]
            if operation == '*':
                result += self.scalars[constants.MULTIPLY]
            elif operation == '-':
                result += self.scalars[constants.SUBTRACT]
            elif operation == '+':
                result += self.scalars[constants.ADD]
            elif operation == '/':
                result += self.scalars[constants.DIVIDE]
            elif operation == '%':
                result += self.scalars[constants.MODULO]
            elif operation in {'<', '<=', '==', '!=' '>=', '>'}:
                result += self.scalars[constants.COMPARISON_OPERATORS]
            elif operation in {'&&', '||'}:
                result += self.scalars[constants.LOGICAL_OPERATORS]
        if node.kind == clang.cindex.CursorKind.COMPOUND_ASSIGNMENT_OPERATOR:
            operation = get_binary_operation(node)[0]
            if operation == '*=':
                result += self.scalars[constants.MULTIPLY]
            elif operation == '-=':
                result += self.scalars[constants.SUBTRACT]
            elif operation == '+=':
                result += self.scalars[constants.ADD]
            elif operation == '/=':
                result += self.scalars[constants.DIVIDE]
            elif operation == '%=':
                result += self.scalars[constants.MODULO]
        if node.kind == clang.cindex.CursorKind.UNARY_OPERATOR:
            operation = get_unary_operation(node)
            if operation == '!':
                result += self.scalars[constants.LOGICAL_OPERATORS]
            else:
                result += self.scalars[constants.INCREMENT]
        elif node.kind == clang.cindex.CursorKind.FOR_STMT:
            result += self.scalars[constants.FOR_STATEMENT]
        elif node.kind == clang.cindex.CursorKind.WHILE_STMT:
            result += self.scalars[constants.WHILE_STATEMENT]
        elif node.kind == clang.cindex.CursorKind.DO_STMT:
            result += self.scalars[constants.DO_STATEMENT]
        elif node.kind == clang.cindex.CursorKind.IF_STMT:
            result += self.scalars[constants.IF_STATEMENT]

        result *= self.row_counts.get(node.extent.start.line, 0)

        for child in node.get_children():
            result += self._dfs(child)

        return result

    def calculate_complexity(self):
        return self._dfs(self.ast)


class CodeProcessor:

    def __init__(self, code):
        self.code = code
        self.filename = md5(code.encode()).hexdigest()[:10]
        self.abs_filename = os.path.join("/Users/denismaznikar/Documents/diplomska/diplomska-master/codes", self.filename)

    def _source_code_filename(self):
        return self.filename + '.cpp'

    def _executable_filename(self):
        return self.filename + '.out'

    def _coverage_filename(self):
        return self._source_code_filename() + '.gcov.json.gz'

    def _save_code(self):
        os.chdir("/Users/denismaznikar/Documents/diplomska/diplomska-master/codes")
        os.system('rm ' + str(self.filename) + '*')
        with open(self._source_code_filename(), 'w') as f:
            f.write(self.code)

    def _load_gzipped_json(self):
        with gzip.GzipFile(self._coverage_filename(), 'r') as f:
            return json.loads(f.read().decode('utf-8'))

    def _compile_for_gcov(self):
        self._save_code()
        os.chdir("/Users/denismaznikar/Documents/diplomska/diplomska-master/codes")
        os.system('g++ -fprofile-arcs -ftest-coverage ' + self._source_code_filename() + ' -o ' + self._executable_filename())

    def _standard_compile(self):
        self._save_code()
        os.chdir("/Users/denismaznikar/Documents/diplomska/diplomska-master/codes")
        return os.system('g++ ' + self._source_code_filename() + ' -o ' + self._executable_filename())

    def _execute_file(self):
        os.chdir("/Users/denismaznikar/Documents/diplomska/diplomska-master/codes")
        return os.popen('./' + self._executable_filename()).read()

    def _run_gcov(self):
        os.chdir("/Users/denismaznikar/Documents/diplomska/diplomska-master/codes")
        os.system('gcov -r -i ' + self._source_code_filename())

    # DFS
    def _extract_key_kinds_from_tree(self, node):
        result = list()
        if str(node.extent.start.file) != self._source_code_filename() and node.extent.start.file != None:
            return result
        if node.kind == clang.cindex.CursorKind.INTEGER_LITERAL:
            result.append(
                ((node.extent.start.line, node.extent.start.column),
                 (node.extent.end.line, node.extent.end.column),
                 list(node.get_tokens())[0].spelling,
                 'integer')
            )
        elif node.kind == clang.cindex.CursorKind.BINARY_OPERATOR:
            info = get_binary_operation(node)
            if info[0] in {'+', '-', '*', '=', '%', '<=', '<', '>=', '>' '==', '!='}:
                result.append(
                    ((node.extent.start.line, info[1]),
                     (node.extent.end.line, info[2]),
                     info[0],
                     'binary_op')
                )
            elif info[0] in {'&&', '||'}:
                result.append(
                    ((node.extent.start.line, info[1]),
                     (node.extent.end.line, info[2]),
                     info[0],
                     'logical')
                )
        elif node.kind in {clang.cindex.CursorKind.STRING_LITERAL, clang.cindex.CursorKind.CHARACTER_LITERAL}:
            result.append(
                ((node.extent.start.line, node.extent.start.column),
                 (node.extent.end.line, node.extent.end.column),
                 list(node.get_tokens())[0].spelling,
                 'text')
            )
        elif node.kind == clang.cindex.CursorKind.FLOATING_LITERAL:
            result.append(
                ((node.extent.start.line, node.extent.start.column),
                 (node.extent.end.line, node.extent.end.column),
                 list(node.get_tokens())[0].spelling,
                 'float')
            )
        for child in node.get_children():
            result.extend(self._extract_key_kinds_from_tree(child))
        return result

    def _extract_ast(self):
        self._save_code()
        idx = clang.cindex.Index.create()
        tu = idx.parse(self._source_code_filename())
        return tu.cursor

    def get_key_locations(self):
        root = self._extract_ast()
        return self._extract_key_kinds_from_tree(root)

    def _get_line_execution_counts(self):
        self._compile_for_gcov()
        self._execute_file()
        self._run_gcov()
        lines = self._load_gzipped_json()['files'][0]['lines']
        line_dict = dict()
        for line in lines:
            line_dict[line['line_number']] = line['count']
        return line_dict

    def get_output(self):
        self._standard_compile()
        output = self._execute_file()
        return output.strip()

    def get_complexity(self, scalars):
        cc = ComplexityCalculator(self._extract_ast(), self._get_line_execution_counts(), self._source_code_filename(), scalars)
        return cc.calculate_complexity()

    def check_compilation(self):
        return self._standard_compile() == 0

class KeyLocation():
    def __init__(self, kl):
        self.start_row = kl[0][0]
        self.start_column = kl[0][1]
        self.end_row = kl[1][0]
        self.end_column = kl[1][1]
        self.value = kl[2]
        self.location_type = kl[3]
        self.range = (-3, 3)
        self.extra_info = "-;*";

    def generate_variation(self):
        if self.location_type == "binary_op":
            choices = self.extra_info.split(";")
            if len(choices) == 0:
                return self.value
            return choice(choices)
        if self.location_type == "integer":
            x, y = self.range
            numbers = list()
            for i in range(x, y):
                numbers.append(i)
            return str(choice(numbers))
        return self.value

def _str_replace(s, si, ei, new):
    return s[:si] + new + s[ei:]

def construct_code(code, key_locations, new_variation_values):
    source_code = code.split('\n')
    variations = list(zip(key_locations, new_variation_values))
    variations.sort(key=lambda x: (x[0].start_row, -x[0].start_column))
    for i, variation in enumerate(variations):
        row = variation[0].start_row - 1
        cs, ce = variation[0].start_column - 1, variation[0].end_column - 1
        source_code[row] = _str_replace(source_code[row], cs, ce, variation[1])
    new_source_code = '\n'.join(source_code)
    return new_source_code

def get_kl(code):
    cp = CodeProcessor(code)
    key_locations = cp.get_key_locations()
    final_list = list()
    for element in key_locations:
        temp_list = list()
        temp_list.append(str(element[0][0]))
        temp_list.append(str(element[0][1]))
        temp_list.append(str(element[1][0]))
        temp_list.append(str(element[1][1]))
        temp_list.append(str(element[2]))
        temp_list.append(str(element[3]))
        final_list.append(";".join(temp_list))
    return final_list


# f = open("/Applications/MAMP/htdocs/moodle37/question/type/finki/temp.txt", "r")
# code = f.read()
# #print code

# output = {}
# cp = CodeProcessor(code)
# if cp._standard_compile():
#     print "NO"
# else:
#     print "YES"
# key_locations = cp.get_key_locations()
# f = open("/Applications/MAMP/htdocs/moodle37/question/type/finki/editable.txt", "w")
# cnt = 0
# for element in key_locations:
#     temp_list = list()
#     temp_list.append(str(element[0][0]))
#     temp_list.append(str(element[0][1]))
#     temp_list.append(str(element[1][0]))
#     temp_list.append(str(element[1][1]))
#     temp_list.append(str(element[2]))
#     temp_list.append(str(element[3]))
#     f.write(";".join(temp_list))
#     cnt = cnt + 1
#     if (cnt < len(key_locations)):
#         f.write("\n")
# f.close()