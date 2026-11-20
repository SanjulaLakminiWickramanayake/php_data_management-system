<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';

function module_definitions(): array
{
    return [
        'students' => [
            'title' => 'Students', 'singular' => 'Student', 'id' => 'student_id', 'table' => 'students',
            'description' => 'Maintain student contact and registration records.',
            'fields' => [
                'full_name' => ['label' => 'Full name', 'type' => 'text', 'required' => true, 'max' => 150],
                'email' => ['label' => 'Email address', 'type' => 'email', 'required' => true, 'max' => 150, 'unique' => true],
                'phone' => ['label' => 'Phone', 'type' => 'tel', 'required' => true, 'max' => 20],
                'address' => ['label' => 'Address', 'type' => 'textarea'],
                'registration_date' => ['label' => 'Registration date', 'type' => 'date', 'required' => true],
            ],
            'columns' => ['student_id' => 'ID', 'full_name' => 'Full name', 'email' => 'Email', 'phone' => 'Phone', 'address' => 'Address', 'registration_date' => 'Registered'],
            'search' => ['student_id', 'full_name', 'email', 'phone'],
            'sort' => ['student_id', 'full_name', 'email', 'phone', 'registration_date'],
            'filters' => ['registration_date' => ['type' => 'date', 'label' => 'Registered on']],
        ],
        'courses' => [
            'title' => 'Courses', 'singular' => 'Course', 'id' => 'course_id', 'table' => 'courses',
            'description' => 'Manage course catalog, duration, and tuition fees.',
            'fields' => [
                'course_name' => ['label' => 'Course name', 'type' => 'text', 'required' => true, 'max' => 150],
                'course_code' => ['label' => 'Course code', 'type' => 'text', 'required' => true, 'max' => 50, 'unique' => true],
                'duration' => ['label' => 'Duration', 'type' => 'text', 'max' => 100],
                'course_fee' => ['label' => 'Course fee', 'type' => 'number', 'required' => true, 'min' => '0', 'step' => '0.01'],
                'description' => ['label' => 'Description', 'type' => 'textarea'],
            ],
            'columns' => ['course_id' => 'ID', 'course_name' => 'Course', 'course_code' => 'Code', 'duration' => 'Duration', 'course_fee' => 'Fee', 'description' => 'Description'],
            'search' => ['course_id', 'course_name', 'course_code'],
            'sort' => ['course_id', 'course_name', 'course_code', 'course_fee'],
        ],
        'instructors' => [
            'title' => 'Instructors', 'singular' => 'Instructor', 'id' => 'instructor_id', 'table' => 'instructors',
            'description' => 'Manage instructor profiles and specializations.',
            'fields' => [
                'full_name' => ['label' => 'Full name', 'type' => 'text', 'required' => true, 'max' => 150],
                'email' => ['label' => 'Email address', 'type' => 'email', 'required' => true, 'max' => 150, 'unique' => true],
                'phone' => ['label' => 'Phone', 'type' => 'tel', 'max' => 20],
                'specialization' => ['label' => 'Specialization', 'type' => 'text', 'max' => 150],
                'joining_date' => ['label' => 'Joining date', 'type' => 'date'],
            ],
            'columns' => ['instructor_id' => 'ID', 'full_name' => 'Full name', 'email' => 'Email', 'phone' => 'Phone', 'specialization' => 'Specialization', 'joining_date' => 'Joining date'],
            'search' => ['instructor_id', 'full_name', 'email', 'specialization'],
            'sort' => ['instructor_id', 'full_name', 'email', 'specialization', 'joining_date'],
        ],
        'enrollments' => [
            'title' => 'Enrollments', 'singular' => 'Enrollment', 'id' => 'enrollment_id', 'table' => 'enrollments',
            'description' => 'Track student registrations and course progress.',
            'select' => 'SELECT e.enrollment_id, e.student_id, e.course_id, e.enrollment_date, e.status, e.remarks, s.full_name AS student_name, c.course_name FROM enrollments e INNER JOIN students s ON s.student_id = e.student_id INNER JOIN courses c ON c.course_id = e.course_id',
            'from' => 'enrollments e INNER JOIN students s ON s.student_id = e.student_id INNER JOIN courses c ON c.course_id = e.course_id',
            'fields' => [
                'student_id' => ['label' => 'Student', 'type' => 'relation', 'required' => true, 'source' => 'students'],
                'course_id' => ['label' => 'Course', 'type' => 'relation', 'required' => true, 'source' => 'courses'],
                'enrollment_date' => ['label' => 'Enrollment date', 'type' => 'date', 'required' => true],
                'status' => ['label' => 'Status', 'type' => 'select', 'required' => true, 'options' => ['Pending', 'Active', 'Completed', 'Cancelled']],
                'remarks' => ['label' => 'Remarks', 'type' => 'textarea'],
            ],
            'columns' => ['enrollment_id' => 'ID', 'student_name' => 'Student', 'course_name' => 'Course', 'enrollment_date' => 'Date', 'status' => 'Status', 'remarks' => 'Remarks'],
            'search' => ['e.enrollment_id', 's.full_name', 'c.course_name', 'e.status'],
            'sort' => ['enrollment_id' => 'e.enrollment_id', 'student_name' => 's.full_name', 'course_name' => 'c.course_name', 'enrollment_date' => 'e.enrollment_date', 'status' => 'e.status'],
            'filters' => ['status' => ['type' => 'select', 'label' => 'Status', 'options' => ['Pending', 'Active', 'Completed', 'Cancelled']]],
        ],
        'payments' => [
            'title' => 'Payments', 'singular' => 'Payment', 'id' => 'payment_id', 'table' => 'payments',
            'description' => 'Review student payments and transaction statuses.',
            'select' => 'SELECT p.payment_id, p.student_id, p.amount, p.payment_date, p.payment_method, p.payment_status, s.full_name AS student_name FROM payments p INNER JOIN students s ON s.student_id = p.student_id',
            'from' => 'payments p INNER JOIN students s ON s.student_id = p.student_id',
            'fields' => [
                'student_id' => ['label' => 'Student', 'type' => 'relation', 'required' => true, 'source' => 'students'],
                'amount' => ['label' => 'Amount', 'type' => 'number', 'required' => true, 'min' => '0.01', 'step' => '0.01'],
                'payment_date' => ['label' => 'Payment date', 'type' => 'date', 'required' => true],
                'payment_method' => ['label' => 'Payment method', 'type' => 'select', 'required' => true, 'options' => ['Cash', 'Card', 'Bank Transfer']],
                'payment_status' => ['label' => 'Payment status', 'type' => 'select', 'required' => true, 'options' => ['Paid', 'Pending', 'Refunded']],
            ],
            'columns' => ['payment_id' => 'ID', 'student_name' => 'Student', 'amount' => 'Amount', 'payment_date' => 'Date', 'payment_method' => 'Method', 'payment_status' => 'Status'],
            'search' => ['p.payment_id', 's.full_name', 'p.amount', 'p.payment_method', 'p.payment_status'],
            'sort' => ['payment_id' => 'p.payment_id', 'student_name' => 's.full_name', 'amount' => 'p.amount', 'payment_date' => 'p.payment_date', 'payment_method' => 'p.payment_method', 'payment_status' => 'p.payment_status'],
            'filters' => [
                'payment_status' => ['type' => 'select', 'label' => 'Payment status', 'options' => ['Paid', 'Pending', 'Refunded']],
                'payment_method' => ['type' => 'select', 'label' => 'Method', 'options' => ['Cash', 'Card', 'Bank Transfer']],
                'date_from' => ['type' => 'date', 'label' => 'From date', 'column' => 'payment_date'],
                'date_to' => ['type' => 'date', 'label' => 'To date', 'column' => 'payment_date'],
            ],
        ],
    ];
}

function module_definition(string $key): array
{
    $modules = module_definitions();
    if (!isset($modules[$key])) {
        http_response_code(404);
        exit('Module not found.');
    }
    return $modules[$key];
}

function module_where(string $key, array $definition, ?array $input = null): array
{
    $input ??= $_GET;
    $where = [];
    $params = [];
    $query = trim(input_string($input, 'q'));
    if ($query !== '') {
        $parts = [];
        foreach ($definition['search'] as $index => $column) {
            $name = 'search' . $index;
            $parts[] = $column . ' LIKE :' . $name;
            $params[$name] = '%' . $query . '%';
        }
        $where[] = '(' . implode(' OR ', $parts) . ')';
    }

    foreach ($definition['filters'] ?? [] as $field => $filter) {
        $value = trim(input_string($input, $field));
        if ($value === '') {
            continue;
        }
        if ($filter['type'] === 'select' && in_array($value, $filter['options'], true)) {
            $column = match ($key) {
                'enrollments' => 'e.' . $field,
                'payments' => 'p.' . $field,
                default => $field,
            };
            $where[] = $column . ' = :filter_' . $field;
            $params['filter_' . $field] = $value;
        } elseif ($filter['type'] === 'date' && valid_date($value)) {
            $column = $filter['column'] ?? $field;
            if ($key === 'enrollments') {
                $column = 'e.' . $column;
            } elseif ($key === 'payments') {
                $column = 'p.' . $column;
            }
            if ($field === 'date_from') {
                $where[] = $column . ' >= :filter_' . $field;
            } elseif ($field === 'date_to') {
                $where[] = $column . ' <= :filter_' . $field;
            } else {
                $where[] = $column . ' = :filter_' . $field;
            }
            $params['filter_' . $field] = $value;
        }
    }

    return [$where ? ' WHERE ' . implode(' AND ', $where) : '', $params];
}

function module_rows(string $key, bool $paginate = false, ?array $input = null): array
{
    $input ??= $_GET;
    $definition = module_definition($key);
    [$where, $params] = module_where($key, $definition, $input);
    $select = $definition['select'] ?? 'SELECT * FROM ' . $definition['table'];
    $orderKey = input_string($input, 'sort');
    $sortMap = [];
    foreach ($definition['sort'] as $name => $sqlColumn) {
        $sortMap[is_int($name) ? $sqlColumn : $name] = is_int($name) ? $sqlColumn : $sqlColumn;
    }
    $orderColumn = $sortMap[$orderKey] ?? array_values($sortMap)[0];
    $direction = strtolower(input_string($input, 'dir', 'asc')) === 'desc' ? 'DESC' : 'ASC';

    $count = db()->prepare('SELECT COUNT(*) FROM ' . ($definition['from'] ?? $definition['table']) . $where);
    $count->execute($params);
    $total = (int) $count->fetchColumn();

    $sql = $select . $where . ' ORDER BY ' . $orderColumn . ' ' . $direction;
    if ($paginate) {
        $pageSize = (int) input_string($input, 'per_page', '10');
        if (!in_array($pageSize, [10, 25, 50, 100], true)) {
            $pageSize = 10;
        }
        $pages = max(1, (int) ceil($total / $pageSize));
        $page = min(max(1, (int) input_string($input, 'page', '1')), $pages);
        $sql .= ' LIMIT ' . $pageSize . ' OFFSET ' . (($page - 1) * $pageSize);
    }
    $statement = db()->prepare($sql);
    $statement->execute($params);

    return ['rows' => $statement->fetchAll(), 'total' => $total];
}

function module_options(string $source): array
{
    if ($source === 'students') {
        return db()->query('SELECT student_id AS id, full_name AS label FROM students ORDER BY full_name')->fetchAll();
    }
    if ($source === 'courses') {
        return db()->query("SELECT course_id AS id, CONCAT(course_name, ' (', course_code, ')') AS label FROM courses ORDER BY course_name")->fetchAll();
    }
    throw new InvalidArgumentException('Unsupported relation source.');
}

function validate_module_input(array $definition, array $input, ?int $id = null): array
{
    $data = [];
    $errors = [];
    foreach ($definition['fields'] as $name => $field) {
        $value = trim(input_string($input, $name));
        if ($value === '') {
            if (!empty($field['required'])) {
                $errors[$name] = $field['label'] . ' is required.';
            }
            $data[$name] = null;
            continue;
        }
        if (isset($field['max']) && mb_strlen($value) > $field['max']) {
            $errors[$name] = $field['label'] . ' must be ' . $field['max'] . ' characters or fewer.';
        } elseif ($field['type'] === 'email' && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
            $errors[$name] = 'Enter a valid email address.';
        } elseif ($field['type'] === 'tel' && !preg_match('/^[0-9+().\-\s]{6,20}$/', $value)) {
            $errors[$name] = 'Enter a valid phone number.';
        } elseif ($field['type'] === 'date' && !valid_date($value)) {
            $errors[$name] = 'Enter a valid date.';
        } elseif ($field['type'] === 'number' && (!is_numeric($value) || (float) $value < (float) ($field['min'] ?? 0) || !preg_match('/^\d{1,8}(\.\d{1,2})?$/', $value))) {
            $errors[$name] = $field['label'] . ' must be a valid non-negative amount with up to two decimals.';
        } elseif ($field['type'] === 'select' && !in_array($value, $field['options'], true)) {
            $errors[$name] = 'Choose a valid ' . strtolower($field['label']) . '.';
        } elseif ($field['type'] === 'relation') {
            $idValue = filter_var($value, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
            $allowed = module_options($field['source']);
            $allowedIds = array_map(static fn(array $option): int => (int) $option['id'], $allowed);
            if ($idValue === false || !in_array((int) $idValue, $allowedIds, true)) {
                $errors[$name] = 'Choose an existing ' . strtolower($field['label']) . '.';
            } else {
                $value = (string) $idValue;
            }
        }
        $data[$name] = $value === '' ? null : $value;
    }

    foreach ($definition['fields'] as $name => $field) {
        if (empty($field['unique']) || empty($data[$name])) {
            continue;
        }
        $sql = 'SELECT COUNT(*) FROM ' . $definition['table'] . ' WHERE ' . $name . ' = :value';
        if ($id !== null) {
            $sql .= ' AND ' . $definition['id'] . ' <> :id';
        }
        $statement = db()->prepare($sql);
        $statement->bindValue(':value', $data[$name]);
        if ($id !== null) {
            $statement->bindValue(':id', $id, PDO::PARAM_INT);
        }
        $statement->execute();
        if ((int) $statement->fetchColumn() > 0) {
            $errors[$name] = $field['label'] . ' is already in use.';
        }
    }
    return [$data, $errors];
}

function module_insert(string $key, array $data): void
{
    $definition = module_definition($key);
    $names = array_keys($definition['fields']);
    $sql = 'INSERT INTO ' . $definition['table'] . ' (' . implode(', ', $names) . ') VALUES (:' . implode(', :', $names) . ')';
    $statement = db()->prepare($sql);
    $statement->execute($data);
}

function module_update(string $key, int $id, array $data): void
{
    $definition = module_definition($key);
    $assignments = [];
    foreach (array_keys($definition['fields']) as $field) {
        $assignments[] = $field . ' = :' . $field;
    }
    $data['record_id'] = $id;
    $sql = 'UPDATE ' . $definition['table'] . ' SET ' . implode(', ', $assignments) . ' WHERE ' . $definition['id'] . ' = :record_id';
    db()->prepare($sql)->execute($data);
}

function module_record(string $key, int $id): ?array
{
    $definition = module_definition($key);
    $statement = db()->prepare('SELECT * FROM ' . $definition['table'] . ' WHERE ' . $definition['id'] . ' = :id');
    $statement->execute(['id' => $id]);
    $record = $statement->fetch();
    return $record ?: null;
}

function module_delete(string $key, int $id): void
{
    $definition = module_definition($key);
    $statement = db()->prepare('DELETE FROM ' . $definition['table'] . ' WHERE ' . $definition['id'] . ' = :id');
    $statement->execute(['id' => $id]);
}
