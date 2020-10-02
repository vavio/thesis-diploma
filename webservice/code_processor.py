from hashlib import md5
from helper_methods import *
from complexity_calculator import ComplexityCalculator
from subprocess import STDOUT, check_output
import config
import os
import gzip
import json
import clang.cindex


class CodeProcessor:

    def __init__(self, code, key_locations=None, new_variation_values=None):
        code = code.replace('\xa0', ' ')

        if key_locations is not None and new_variation_values is not None:
            source_code = code.split('\n')
            variations = list(zip(key_locations, new_variation_values))
            variations.sort(key=lambda x: (x[0].start_line, -x[0].start_column))
            for variation in variations:
                row = variation[0].start_line - 1
                cs, ce = variation[0].start_column - 1, variation[0].end_column - 1
                source_code[row] = self._str_replace(source_code[row], cs, ce, variation[1])
            self.code = '\n'.join(source_code)
        else:
            self.code = code

        self.filename = md5(code.encode()).hexdigest()[:10]
        self.working_dir = config.WORKING_CODES_DIR
        self.abs_filename = os.path.join(self.working_dir, self.filename)

    @staticmethod
    def _str_replace(s, si, ei, new):
        return s[:si] + new + s[ei:]

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
            'g++-10 -fprofile-arcs -ftest-coverage ' + self._source_code_filename() + ' -o ' +
            self._executable_filename())

    def _standard_compile(self):
        self._save_code()
        os.chdir(self.working_dir)
        return os.system('g++-10 ' + self._source_code_filename() + ' -o ' + self._executable_filename())

    def _execute_file(self):
        os.chdir(self.working_dir)
        return check_output('./' + self._executable_filename(), stderr=STDOUT, timeout=config.EXECUTION_TIMEOUT).decode(
            'utf-8')

    def _run_gcov(self):
        os.chdir(self.working_dir)
        os.system('gcov-10 -r -i ' + self._source_code_filename())

    # DFS
    def _extract_key_kinds_from_tree(self, node, suggested=None):
        if node.extent.start.file is not None and str(node.extent.start.file) != self._source_code_filename():
            return list()

        if node.kind == clang.cindex.CursorKind.RETURN_STMT:
            return list()

        result = list()
        # This whole method can be improved
        if node.kind == clang.cindex.CursorKind.INTEGER_LITERAL:
            result.append(get_integer_data(node))
        elif node.kind == clang.cindex.CursorKind.FLOATING_LITERAL:
            result.append(get_float_data(node))
        elif node.kind == clang.cindex.CursorKind.BINARY_OPERATOR:
            binary_data = get_binary_data(node)
            if binary_data is None:
                pass
            result.append(binary_data)
        elif node.kind in {clang.cindex.CursorKind.STRING_LITERAL, clang.cindex.CursorKind.CHARACTER_LITERAL}:
            result.append(get_string_data(node, node.kind == clang.cindex.CursorKind.STRING_LITERAL))
        elif node.kind == clang.cindex.CursorKind.UNARY_OPERATOR:
            unary_data = get_unary_data(node)
            if unary_data is None:
                pass
            result.append(unary_data)

        children_count = len(list(node.get_children()))
        # if node.kind == clang.cindex.CursorKind.INIT_LIST_EXPR and suggested is not None:
        #     suggested = self.extract_range(node)

        for (idx, child) in enumerate(node.get_children()):
            if self.is_negative_number(node, child):
                literal_value = self._extract_key_kinds_from_tree(child)
                if len(literal_value) == 1:
                    literal_value = literal_value[0]
                    literal_value['start_column'] = literal_value['start_column'] - 1
                    result.append(literal_value)
                    continue

            if self.is_formatting_string(node, idx):
                continue

            if self.is_array_length(node, child, children_count):
                continue

            result.extend(self._extract_key_kinds_from_tree(child, suggested))

        return result

    # @staticmethod
    # def extract_range(node):
    #     if node.kind == clang.cindex.CursorKind.INTEGER_LITERAL:

    @staticmethod
    def is_negative_number(node, child):
        # This is handling the negative int/float literals
        if node.kind != clang.cindex.CursorKind.UNARY_OPERATOR:
            return False

        if child.kind not in {clang.cindex.CursorKind.INTEGER_LITERAL, clang.cindex.CursorKind.FLOATING_LITERAL}:
            return False

        return True

    @staticmethod
    def is_formatting_string(node, idx):
        # This is handling the printf/scanf and fprintf/fscanf
        if node.kind != clang.cindex.CursorKind.CALL_EXPR:
            return False

        name = node.displayname
        if name in {'printf', 'scanf'} and idx < 2:
            # first child is the name printf/scanf
            # second child is the formatting string
            return True

        if name in {'fprintf', 'fscanf'} and idx < 3:
            # first child is the name fprintf/fscanf
            # second child is the file pointer
            # third child is the formatting string
            return True

        return False

    @staticmethod
    def is_array_length(node, child, children_count):
        # This is handling the array definitions
        if node.kind != clang.cindex.CursorKind.VAR_DECL:
            return False

        if children_count <= 1:
            return False

        if child.kind != clang.cindex.CursorKind.INTEGER_LITERAL:
            return False

        return True

    def _extract_ast(self):
        self._save_code()
        idx = clang.cindex.Index.create()
        print(self._source_code_filename())
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
