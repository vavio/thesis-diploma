from hashlib import md5
import clang.cindex
import dbm
import os
import gzip
import json
import re
from random import choice, random
from subprocess import STDOUT, check_output, TimeoutExpired
import string
import config


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

    def __init__(self, ast, row_counts, ignore_filename):
        self.ast = ast
        self.row_counts = row_counts
        self.ignore_filename = ignore_filename

        with dbm.open(os.path.join(config.WORKING_CODES_DIR, config.DB_FILE), 'c') as db:
            self.weights = {
                clang.cindex.CursorKind.FOR_STMT: 'FOR_STATEMENT',
                clang.cindex.CursorKind.WHILE_STMT: 'WHILE_STATEMENT',
                clang.cindex.CursorKind.DO_STMT: 'DO_STATEMENT',
                clang.cindex.CursorKind.IF_STMT: 'IF_STATEMENT',

                '+': 'ADD',
                '+=': 'ADD',

                '-': 'SUBTRACT',
                '-=': 'SUBTRACT',

                '*': 'MULTIPLY',
                '*=': 'MULTIPLY',

                '/': 'DIVIDE',
                '/=': 'DIVIDE',

                '%': 'MODULO',
                '%=': 'MODULO',

                '<': 'COMPARISON_OPERATORS',
                '<=': 'COMPARISON_OPERATORS',
                '==': 'COMPARISON_OPERATORS',
                '!=': 'COMPARISON_OPERATORS',
                '>=': 'COMPARISON_OPERATORS',
                '>': 'COMPARISON_OPERATORS',

                '!': 'LOGICAL_OPERATORS',
                '&&': 'LOGICAL_OPERATORS',
                '||': 'LOGICAL_OPERATORS',

                '++': 'INCREMENT',
                '--': 'INCREMENT'
            }

            for key in self.weights.keys():
                value = self.weights[key]
                self.weights[key] = float(db[value].decode('utf-8'))

    def _dfs(self, node):
        if str(node.extent.start.file) != self.ignore_filename and node.extent.start.file is not None:
            return 0

        key = None

        if node.kind in {clang.cindex.CursorKind.BINARY_OPERATOR, clang.cindex.CursorKind.COMPOUND_ASSIGNMENT_OPERATOR} :
            key = get_binary_operation(node)[0]
        elif node.kind == clang.cindex.CursorKind.UNARY_OPERATOR:
            key = get_unary_operation(node)
        elif node.kind in {clang.cindex.CursorKind.FOR_STMT, clang.cindex.CursorKind.WHILE_STMT, clang.cindex.CursorKind.DO_STMT, clang.cindex.CursorKind.IF_STMT}:
            key = node.kind

        result = 0
        if key is not None:
            result = self.weights.get(key, 0) * self.row_counts.get(node.extent.start.line, 0)

        for child in node.get_children():
            result += self._dfs(child)

        return result

    def calculate_complexity(self):
        return self._dfs(self.ast)


class CodeProcessor:

    def __init__(self, code):
        self.code = code
        self.filename = md5(code.encode()).hexdigest()[:10]
        self.working_dir = config.WORKING_CODES_DIR
        self.abs_filename = os.path.join(self.working_dir, self.filename)

    def _source_code_filename(self):
        return self.filename + '.cpp'

    def _executable_filename(self):
        return self.filename + '.out'

    def _coverage_filename(self):
        return self._source_code_filename() + '.gcov.json.gz'

    def _save_code(self):
        os.chdir(self.working_dir)
        os.system('rm ' + str(self.filename) + '*' + ' ||:')
        with open(self._source_code_filename(), 'w') as f:
            f.write(self.code)

    def _load_gzipped_json(self):
        with gzip.GzipFile(self._coverage_filename(), 'r') as f:
            return json.loads(f.read().decode('utf-8'))

    def _compile_for_gcov(self):
        self._save_code()
        os.chdir(self.working_dir)
        os.system(
            'g++-10 -fprofile-arcs -ftest-coverage ' + self._source_code_filename() + ' -o ' + self._executable_filename())

    def _standard_compile(self):
        self._save_code()
        os.chdir(self.working_dir)
        return os.system('g++-10 ' + self._source_code_filename() + ' -o ' + self._executable_filename())

    def _execute_file(self):
        os.chdir(self.working_dir)
        return check_output('./' + self._executable_filename(), stderr=STDOUT, timeout=config.EXECUTION_TIMEOUT).decode('utf-8')

    def _run_gcov(self):
        os.chdir(self.working_dir)
        os.system('gcov-10 -r -i ' + self._source_code_filename())

    # DFS
    def _extract_key_kinds_from_tree(self, node):
        result = list()
        if str(node.extent.start.file) != self._source_code_filename() and node.extent.start.file is not None:
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
        tu = idx.parse(self._source_code_filename(), ['-I', config.CLANG_LIBRARY_DIR])
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

    def get_complexity(self):
        cc = ComplexityCalculator(self._extract_ast(), self._get_line_execution_counts(), self._source_code_filename())
        return cc.calculate_complexity()

    def check_compilation(self):
        return self._standard_compile() == 0


class KeyLocation:
    re_value = re.compile(r'^-?\d+$')
    re_range = re.compile(r'^-?\d+:-?\d+$')
    re_exclude = re.compile(r'^\^-?\d')

    def __init__(self, kl):
        self.start_row = kl[0][0]
        self.start_column = kl[0][1]
        self.end_row = kl[1][0]
        self.end_column = kl[1][1]
        self.value = kl[2]
        self.location_type = kl[3]
        self.extra_info = ""

    def set_extra_info(self, line):
        self.extra_info = line

    def generate_variation(self):
        if self.location_type == "binary_op" or self.location_type == "logical":
            if self.extra_info == "":
                return self.value
            return choice(self.extra_info.split(";"))

        if self.location_type == "integer":
            # TODO VVV implement for float
            if self.extra_info == "":
                return self.value
            splitted = self.extra_info.split(",")
            numbers = list()
            excluded = set()
            for s in splitted:
                if KeyLocation.re_value.match(s):
                    numbers.append(int(s))
                elif KeyLocation.re_range.match(s):
                    splitted_range = s.split(':')
                    for val in range(int(splitted_range[0]), int(splitted_range[1])):
                        numbers.append(val)
                elif KeyLocation.re_exclude.match(s):
                    excluded.add(int(s[1:]))

            numbers = [n for n in numbers if n not in excluded]
            return str(choice(numbers))

        if self.location_type == "float":
            if self.extra_info == "":
                return self.value
            temp = self.extra_info.split(";")
            result = round(random() * (float(temp[1]) - float(temp[0])) + float(temp[0]), 2)
            return str(result)

        if self.location_type == "text":
            if self.extra_info == "":
                return self.value
            choices = list()
            splitted = self.extra_info.split(";")
            for s in splitted:
                if s == "lowercase":
                    choices.extend(string.ascii_lowercase)
                elif s == "uppercase":
                    choices.extend(string.ascii_uppercase)
                elif s == "digits":
                    choices.extend(string.digits)
            random_text = ''.join([choice(choices) for _ in range(len(self.value) - 2)])
            return ''.join([self.value[0], random_text, self.value[-1]])
        return self.value


def _str_replace(s, si, ei, new):
    return s[:si] + new + s[ei:]


def construct_code(code, key_locations, new_variation_values):
    source_code = code.split('\n')
    variations = list(zip(key_locations, new_variation_values))
    variations.sort(key=lambda x: (x[0].start_row, -x[0].start_column))
    for variation in variations:
        row = variation[0].start_row - 1
        cs, ce = variation[0].start_column - 1, variation[0].end_column - 1
        source_code[row] = _str_replace(source_code[row], cs, ce, variation[1])
    new_source_code = '\n'.join(source_code)
    return new_source_code


def get_key_locations(code):
    cp = CodeProcessor(code)
    key_locations = cp.get_key_locations()
    return_list = list()

    for element in key_locations:
        temp_list = [element[0][0], element[0][1], element[1][0], element[1][1], element[2], element[3]]
        return_list.append(';'.join(map(str, temp_list)))

    print(return_list)
    return return_list


def generate_variation(code, edit):
    code = code.replace('\xa0', ' ')
    cp = CodeProcessor(code)
    key_locations = cp.get_key_locations()
    output = {}
    return_list = list()
    for i in range(30):
        new_variation = list()
        key_loc = list()
        for (idx, key_location) in enumerate(key_locations):
            kl = KeyLocation(key_location)
            kl.set_extra_info(edit[idx])
            key_loc.append(kl)
            new_variation.append(kl.generate_variation())
        new_source_code = construct_code(code, key_loc, new_variation)
        new_cp = CodeProcessor(new_source_code)
        if new_source_code not in output:
            try:
                code_output = new_cp.get_output()
            except TimeoutExpired:
                print('Timed out for ' + new_cp.filename)
                continue

            if len(code_output) == 0:
                continue

            output[new_source_code] = code_output
            return_list.append({
                'difficulty': new_cp.get_complexity(),
                'new_source_code': new_source_code,
                'output': output[new_source_code]
            })
            # print(new_source_code)
    return return_list
