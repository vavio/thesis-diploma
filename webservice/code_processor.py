from hashlib import md5
from suggestion_methods import *
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
    def _extract_key_kinds_from_tree(self, node, suggested=None, var_suggestions=None) -> list:
        if var_suggestions is None:
            var_suggestions = dict()

        if node.extent.start.file is not None and str(node.extent.start.file) != self._source_code_filename():
            return list()

        if node.kind == clang.cindex.CursorKind.RETURN_STMT:
            return list()

        result = list()
        # This whole method can be improved
        if node.kind == clang.cindex.CursorKind.INTEGER_LITERAL:
            result.append(get_integer_data(node, suggested))
        elif node.kind == clang.cindex.CursorKind.FLOATING_LITERAL:
            result.append(get_float_data(node, suggested))
        elif node.kind == clang.cindex.CursorKind.BINARY_OPERATOR:
            binary_data = get_binary_data(node, suggested)
            if binary_data is not None:
                result.append(binary_data)
        elif node.kind in {clang.cindex.CursorKind.STRING_LITERAL, clang.cindex.CursorKind.CHARACTER_LITERAL}:
            result.append(get_string_data(node))
        elif node.kind == clang.cindex.CursorKind.UNARY_OPERATOR:
            unary_data = get_unary_data(node, suggested)
            if unary_data is not None:
                result.append(unary_data)

        children_count = len(list(node.get_children()))

        if suggested is None:
            if node.kind == clang.cindex.CursorKind.INIT_LIST_EXPR:
                values = extract_array_values(node)
                suggested = extract_range(values, False)

        for (idx, child) in enumerate(node.get_children()):
            if is_variable_declaration(node):
                suggested = var_suggestions.get(node.spelling)

            if is_negative_number(node, child):
                # we already added the negative number, no need to add it again
                continue

            if is_formatting_string(node, idx, children_count):
                continue

            if is_array_length(node, child, children_count):
                continue

            result.extend(self._extract_key_kinds_from_tree(child,
                                                            suggested=suggested,
                                                            var_suggestions=var_suggestions))

        return result

    def _extract_ast(self):
        self._save_code()
        idx = clang.cindex.Index.create()
        print(self._source_code_filename())
        tu = idx.parse(self._source_code_filename(), ['-I', config.CLANG_LIBRARY_DIR])
        return tu.cursor

    def get_key_locations(self):
        root = self._extract_ast()
        var_suggestions = self._get_var_suggestion(root)
        retValue = self._extract_key_kinds_from_tree(root, suggested=None, var_suggestions=var_suggestions)

        for value in retValue:
            if 'suggestion' in value:
                continue

            if value['type'] in {'integer', 'float'}:
                varValue = value['value']
                value['suggested'] = '{start}:{end}'.format(start=varValue - 8, end=varValue + 8)

        return retValue

    def _get_var_suggestion(self, node) -> dict:
        if node.extent.start.file is not None and str(node.extent.start.file) != self._source_code_filename():
            return dict()

        if node.kind == clang.cindex.CursorKind.RETURN_STMT:
            return dict()

        if node.kind == clang.cindex.CursorKind.SWITCH_STMT:
            name = None
            values = set()
            for (idx, child) in enumerate(node.get_children()):
                if child.kind.is_unexposed():
                    name = child.spelling
                    continue

                values = values.union(extract_switch_values(child))

            suggested = extract_range(values, True)
            # print(name)
            # print(suggested)
            return {name: suggested} if len(suggested) != 0 else {}

        ret_value = {}

        for child in node.get_children():
            ret_value = {**ret_value, **self._get_var_suggestion(child)}

        return ret_value

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

    def check_compilation(self) -> bool:
        return self._standard_compile() == 0
