import clang.cindex
from helper_methods import *


def check_increasing(seq: list, start: int):
    # This will check if it is increasing:
    # If it is return -1 else return the element at which is it not increasing
    for i in range(start, len(seq)-1):
        if seq[i] + 1 != seq[i+1]:
            return i
    return -1


def extract_range(set_values: set):
    values = sorted(set_values)

    length = len(values)
    if length == 1:
        return str(values[0])

    if isinstance(values[0], float):
        return "{}:{}".format(values[0], values[-1])

    ret_value = []
    start = 0

    while start < length:
        end = check_increasing(values, start)

        if end == -1:
            # ??? end of the list or 1 element
            s = values[start]
            e = values[end]
            if s == e:
                ret_value.append(str(s))
            else:
                ret_value.append("{}:{}".format(s, e))
            break

        if start == end:
            ret_value.append(str(values[start]))
        else:
            ret_value.append("{}:{}".format(values[start], values[end]))

        start = end + 1

    return ";".join(ret_value)


def extract_values(node):
    if node.kind == clang.cindex.CursorKind.INTEGER_LITERAL:
        return get_integer_data(node, None)['value']

    if node.kind == clang.cindex.CursorKind.FLOATING_LITERAL:
        return get_float_data(node, None)['value']

    if node.kind == clang.cindex.CursorKind.UNARY_OPERATOR:
        return get_unary_data(node, None)['value']

    # multi dimensional array
    values = set()

    for child in node.get_children():
        child_value = extract_values(child)
        if isinstance(child_value, set):
            values = values.union(child_value)
        else:
            values.add(child_value)

    return values