import clang.cindex


def get_suggested(suggested) -> dict:
    if suggested is None:
        return {}

    return {'suggested': suggested}


def get_integer_data(node, suggested) -> dict:
    return {
        'start_line': node.extent.start.line,
        'start_column': node.extent.start.column,
        'end_line': node.extent.end.line,
        'end_column': node.extent.end.column,
        'value': int(list(node.get_tokens())[0].spelling),
        'type': 'integer',
        **get_suggested(suggested)
    }


def get_float_data(node, suggested) -> dict:
    return {
        'start_line': node.extent.start.line,
        'start_column': node.extent.start.column,
        'end_line': node.extent.end.line,
        'end_column': node.extent.end.column,
        'value': float(list(node.get_tokens())[0].spelling),
        'type': 'float',
        **get_suggested(suggested)
    }


def get_binary_data(node, suggested):
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
                'type': operation_type,
                **get_suggested(suggested)
            }

    return None


def get_string_data(node, suggested) -> dict:
    return {
        'start_line': node.extent.start.line,
        'start_column': node.extent.start.column,
        'end_line': node.extent.end.line,
        'end_column': node.extent.end.column,
        'value': list(node.get_tokens())[0].spelling,
        'type': 'text' if node.kind == clang.cindex.CursorKind.STRING_LITERAL else 'character',
        **get_suggested(suggested)
    }


def get_unary_data(node, suggested):
    tokens = list(node.get_tokens())
    for token in tokens:
        if token.spelling in {'--', '++'}:
            return {
                'start_line': node.extent.start.line,
                'start_column': token.extent.start.column,
                'end_line': node.extent.end.line,
                'end_column': token.extent.end.column,
                'value': token.spelling,
                'type': 'unary_op',
                **get_suggested(suggested)
            }

        if token.spelling in {'-', '+'}:
            data = None
            for child in node.get_children():
                if child.kind == clang.cindex.CursorKind.INTEGER_LITERAL:
                    data = get_integer_data(child, suggested)
                if child.kind == clang.cindex.CursorKind.FLOATING_LITERAL:
                    data = get_float_data(child, suggested)
            if data is None:
                return None

            data['start_column'] = data['start_column'] - 1
            data['value'] = data['value'] * (-1 if token.spelling == '-' else 1)

            return data

    # the !, *, & ... are also unary which we do not handle
    return None


def is_negative_number(node, child) -> bool:
    # This is handling the negative int/float literals
    if node.kind != clang.cindex.CursorKind.UNARY_OPERATOR:
        return False

    if child.kind not in {clang.cindex.CursorKind.INTEGER_LITERAL, clang.cindex.CursorKind.FLOATING_LITERAL}:
        return False

    return True


def is_formatting_string(node, idx: int, children_count: int) -> bool:
    # This is handling the printf/scanf and fprintf/fscanf
    if node.kind != clang.cindex.CursorKind.CALL_EXPR:
        return False

    name = node.displayname
    # TODO this should be improved to ignore ONLY the formatting part of string
    if name in {'printf', 'scanf'} and children_count > 2 > idx:
        # first child is the name printf/scanf
        # second child is the formatting string
        return True

    if name in {'fprintf', 'fscanf'} and children_count > 3 > idx:
        # first child is the name fprintf/fscanf
        # second child is the file pointer
        # third child is the formatting string
        return True

    return False


def is_array_length(node, child, children_count: int) -> bool:
    # This is handling the array definitions
    if node.kind != clang.cindex.CursorKind.VAR_DECL:
        return False

    if children_count <= 1:
        return False

    if child.kind != clang.cindex.CursorKind.INTEGER_LITERAL:
        return False

    return True


def is_variable_declaration(node) -> bool:
    return node.kind == clang.cindex.CursorKind.VAR_DECL
