import mysql.connector
import pprint

pp = pprint.PrettyPrinter()

mydb = mysql.connector.connect(
    host="localhost",
    user="root",
    password="&qoPutEt4tYc)>Er3*2D3agECK7tZW)DFsCAxP4GJcb]s9Hyqr7d/^m6wx7r)sa[",
    database="moodle",
    port="8889"
)


def get_quizes():
    dbcursor = mydb.cursor()
    dbcursor.execute(
        'SELECT mq.id as quiz_id '
        'FROM mdl_quiz mq '
        'LEFT JOIN mdl_quiz_slots mqs ON mq.id = mqs.quizid '
        'WHERE mqs.questionid IN ('
        'SELECT id '
        'FROM mdl_question mq '
        'WHERE mq.qtype = \'codecpp\''
        ')'
    )
    results = dbcursor.fetchall()

    ret = []
    for x in results:
        # print(x)
        ret.append(dict((dbcursor.column_names[idx], v) for (idx, v) in enumerate(x)))

    return ret


def get_attemps(quiz_id):
    dbcursor = mydb.cursor()
    dbcursor.execute(
        'SELECT quiza.id AS attempt,'
        'u.id AS userid,'
        'quiza.timefinish,'
        'quiza.timestart '
        'FROM mdl_user u '
        'LEFT JOIN mdl_quiz_attempts quiza ON quiza.userid = u.id AND quiza.quiz = {quizid} '
        'WHERE quiza.id IS NOT NULL AND quiza.preview = 0 AND (quiza.state = \'finished\' OR quiza.state IS NULL)'.format(
            quizid=quiz_id)
    )

    results = dbcursor.fetchall()

    ret = []
    for x in results:
        ret.append(dict((dbcursor.column_names[idx], v) for (idx, v) in enumerate(x)))
        # print(x)

    return ret


def get_attemp_step_data(attempt_id):
    dbcursor = mydb.cursor()
    dbcursor.execute(
        'SELECT '
        'qa.id AS questionattemptid, '
        'qa.questionid, '
        'qa.questionsummary, '
        'qa.rightanswer, '
        'qa.responsesummary, '
        'qas.id AS attemptstepid, '
        'qas.timecreated, '
        'qas.sequencenumber, '
        'qas.state, '
        'qasd.value '
        'FROM mdl_quiz_attempts qza '
        'LEFT JOIN mdl_question_usages as quba ON qza.uniqueid = quba.id '
        'LEFT JOIN mdl_question_attempts as qa ON qa.questionusageid = quba.id '
        'LEFT JOIN mdl_question_attempt_steps as qas ON qas.questionattemptid = qa.id '
        'LEFT JOIN mdl_question_attempt_step_data as qasd ON qasd.attemptstepid = qas.id '
        'WHERE '
        'qza.id = {attemptid} '
        'ORDER BY '
        'qas.timecreated'.format(attemptid=attempt_id))

    results = dbcursor.fetchall()

    ret = []
    for x in results:
        ret.append(dict((dbcursor.column_names[idx], v) for (idx, v) in enumerate(x)))
        # print(x)

    return ret


def calculate_time(steps):
    idx = 0

    graded = dict((s['questionid'], s['state'] == 'gradedright') for s in steps if s['state'].startswith('graded'))

    while steps[idx]['state'] == 'todo':
        idx += 1

    ret = []
    while not steps[idx]['state'].startswith('graded'):
        question_id = steps[idx]['questionid']
        ret.append({
            'questionid': question_id,
            'time': steps[idx]['timecreated'] - steps[idx-1]['timecreated'],
            'correct': graded[question_id]
        })
        idx += 1

    return ret


quiz_ids = get_quizes()
print(quiz_ids)

attempt_ids = get_attemps(quiz_ids[0]['quiz_id'])
print(attempt_ids)

# attempt_steps = get_attemp_step_data(attempt_ids[0]['attempt'])
attempt_steps = get_attemp_step_data(17)
print(attempt_steps)

question_times = calculate_time(attempt_steps)
print(question_times)
