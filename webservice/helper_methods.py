

def get_integer_data(node):
    return {
        'start_line': node.extent.start.line,
        'start_column': node.extent.start.column,
        'end_line': node.extent.end.line,
        'end_column': node.extent.end.column,
        'value': list(node.get_tokens())[0].spelling,
        'type': 'integer'
    }


def get_float_data(node):
    return {
        'start_line': node.extent.start.line,
        'start_column': node.extent.start.column,
        'end_line': node.extent.end.line,
        'end_column': node.extent.end.column,
        'value': list(node.get_tokens())[0].spelling,
        'type': 'float'
    }


def get_binary_data(node):
    children = list(node.get_children())
    lo = children[0].extent.end.column
    hi = children[1].extent.start.column
    for token in node.get_tokens():
        if lo <= token.extent.start.column <= hi and lo <= token.extent.end.column <= hi:
            if token.spelling in {'+', '-', '*', '=', '%', '<=', '<', '>=', '>' '==', '!='}:
                operation_type = 'binary_op'
            elif token.spelling in {'&&', '||'}:
                operation_type = 'logical'
            else:
                return None

            return {
                'start_line': node.extent.start.line,
                'start_column': token.extent.start.column,
                'end_line': node.extent.end.line,
                'end_column': token.extent.end.column,
                'value': token.spelling,
                'type': operation_type
            }

    return None


def get_string_data(node, is_text):
    return {
        'start_line': node.extent.start.line,
        'start_column': node.extent.start.column,
        'end_line': node.extent.end.line,
        'end_column': node.extent.end.column,
        'value': list(node.get_tokens())[0].spelling,
        'type': 'text' if is_text else 'character'
    }


def get_unary_data(node):
    tokens = list(node.get_tokens())
    for token in tokens:
        if token.spelling in {'--', '++'}:
            return {
                'start_line': node.extent.start.line,
                'start_column': token.extent.start.column,
                'end_line': node.extent.end.line,
                'end_column': token.extent.end.column,
                'value': token.spelling,
                'type': 'unary_op'
            }

    # the !, +, -, *, & ... are also unary which we do not handle
    return None
