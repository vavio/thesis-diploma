

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
        if token.spelling in {'--', '++', '!'}:
            return token.spelling, token.extent.start.column, token.extent.end.column