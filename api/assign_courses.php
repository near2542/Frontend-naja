<?php
require_once('./header.php');
require_once('./Class/JWTauth.php');
require_once('./db_config.php');


// header("Content-Type: application/json");
// header('Access-Control-Allow-Origin: http://localhost:3000');
// header('Access-Control-Allow-Credentials:true');
// header('Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept,ACCESS_TOKEN');
// header("Access-Control-Allow-Methods: GET, POST,PUT,PATCH,DELETE,OPTIONS");
// $jwt = 'test';

function checkDuplicateSection($db, $decode)
{
    try {
        $sqlTest = "";

                    // if PATCH
        if (isset($decode['m_course_id'])) {
           $sqlTest = "SELECT m_course_id FROM matching_course m
           INNER JOIN course c ON    c.id = m.courseID 
           INNER JOIN user_tbl u ON m.user_id = u.user_id
           WHERE m.courseID = :course_id AND section = :section AND c.deleted = 0 AND m.deleted = 0  AND u.deleted = 0 
                 AND sem_id = :sem_id AND m_course_id != :m_id";
            $statementTest = $db->prepare($sqlTest);
            $statementTest->execute([
                ':course_id' => $decode['course_id'],
                ':section' => $decode['section'],
                ':sem_id' => $decode['sem_id'],
                ':m_id' => $decode['m_course_id']
            ]);
        } else { //if POST
            $sqlTest = "SELECT m_course_id FROM matching_course m
           INNER JOIN course c ON    c.id = m.courseID 
           INNER JOIN user_tbl u ON m.user_id = u.user_id
           WHERE m.courseID = :course_id AND section = :section AND c.deleted = 0 AND m.deleted = 0  AND u.deleted = 0 
                 AND sem_id = :sem_id";

            $statementTest = $db->prepare($sqlTest);
            $statementTest->execute([
                ':course_id' => $decode['course_id'],
                ':section' => $decode['section'],
                ':sem_id' => $decode['sem_id'],
            ]);
        }


        $isExistedSection = count($statementTest->fetchAll());
        if ($isExistedSection > 0) {
            http_response_code(403);
            die(json_encode(['error' => 'Duplicated Section']));
        }
        return;
    } catch (Exception $e) {
        die(json_encode($e->getMessage()));
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    try {
        $sql = "SELECT * FROM matching_course m
        INNER JOIN semester s on m.sem_id = s.sem_id
        INNER JOIN course c on m.courseID = c.id
        INNER JOIN day_work d on m.t_date = d.id
        INNER JOIN user_tbl u on m.user_id = u.user_id
        INNER JOIN major ma on ma.major_id = c.major_id
        WHERE m.deleted = 0 and c.deleted = 0";
        if (isset($_GET['user'])) $sql .= " and m.user_id = {$_GET['user']}";
        $sql .= " ORDER BY s.sem_number desc,m.m_status ";

        $row = $db->query($sql)->fetchAll(PDO::FETCH_ASSOC);

        die(json_encode($row));
    } catch (Exception $e) {
        http_response_code(400);
        exit(json_encode($e->getMessage()));
    }
} else if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = file_get_contents('php://input');
    $decode = json_decode($input, true);

    checkDuplicateSection($db, $decode);

    try {

        $sql = "INSERT INTO matching_course values (NULL,:sem_id,:course_id,:section,:day,:work_time,:user_id,:language,:hour,'1','0');";
        $statement = $db->prepare($sql);
        $result = $statement->execute([
            ':sem_id' => $decode['sem_id'],
            ':course_id' => $decode['course_id'],
            ':section' => $decode['section'],
            ':day' => $decode['day'],
            ':work_time' => $decode['work_time'],
            ':user_id' => $decode['user_id'],
            ':language' => $decode['language'],
            ':hour' => $decode['hour'],
        ]);
        die(json_encode($result));
    } catch (Exception $e) {
        http_response_code(400);
        die(['error' => $e->getMessage()]);
    }
} else if ($_SERVER['REQUEST_METHOD'] === 'PATCH') {
    $input = file_get_contents('php://input');
    $decode = json_decode($input, true);
    checkDuplicateSection($db, $decode);
    $sql = "UPDATE matching_course SET  sem_id = :sem_id, courseID = :course_id, section = :section, t_date = :day,
    t_time = :work_time, user_id = :user_id, language = :language, hr_per_week =  :hour, m_status = '1',deleted = '0' where m_course_id = :m_course_id ";
    try {
        $statement = $db->prepare($sql);
        if (!$statement) echo ('test');
        $result = $statement->execute([
            ':sem_id' => $decode['sem_id'],
            ':course_id' => $decode['course_id'],
            ':section' => $decode['section'],
            ':day' => $decode['day'],
            ':work_time' => $decode['work_time'],
            ':user_id' => $decode['user_id'],
            ':language' => $decode['language'],
            ':hour' => $decode['hour'],
            ':m_course_id' => $decode['m_course_id']
        ]);
        die(json_encode($result));
    } catch (PDOException $e) {
        die('test');
        // die(json_encode($e->getMessage()));
    }
} else if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
    if (!isset($_GET['id'])) die(json_encode(['error' => 'Course not existed']));
    $id = $_GET['id'];
    $sql = "UPDATE matching_course SET 
                deleted=1
                WHERE m_course_id=:id;";
    try {
        $statement = $db->prepare($sql);
        $result = $statement->execute([
            ':id' => $id,

        ]);


        die(json_encode($result));
    } catch (Exception $e) {
        die(json_encode($e->getMessage()));
    }
}
