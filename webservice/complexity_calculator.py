import clang.cindex
import dbm
import os
import config
from helper_methods import *


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

        # TODO VVV update
        if node.kind in {clang.cindex.CursorKind.BINARY_OPERATOR, clang.cindex.CursorKind.COMPOUND_ASSIGNMENT_OPERATOR}:
            key = get_binary_data(node, None)
            if key is not None:
                key = key['value']
        elif node.kind == clang.cindex.CursorKind.UNARY_OPERATOR:
            key = get_unary_data(node, None)
            if key is not None:
                key = key['value']
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
