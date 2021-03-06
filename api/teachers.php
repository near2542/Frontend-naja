<?php
require_once './header.php';
require_once './db_config.php';

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    try {
        $sql = "SELECT user_id,username,f_name,l_name,major_name,cmu_mail,tel,u.major_id from user_tbl  u
        INNER JOIN major m ON u.major_id = m.major_id  
        WHERE  user_type = 4 and u.deleted = 0;";
        $row = $db->query($sql)->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode($row);
    } catch (Exception $e) {
        http_response_code(400);
        echo json_encode($e->getMessage());
    }

    exit(-1);
}


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = file_get_contents('php://input');
    $decode = json_decode($input, true);
    if (!count((array)($decode)) || $_SERVER['REQUEST_METHOD'] !== 'POST') {
        echo json_encode(['status' => 'something went wrong']);
        exit(-1);
    }
    if (
        is_null($decode['cmu_mail'])
        || is_null($decode['password'])
        || is_null($decode['f_name'])
        || is_null($decode['l_name'])
        || is_null($decode['tel'])
    ) {
        http_response_code(400);
        echo json_encode(['status' => 'something went wrong']);
        exit(-1);
    }

    try {
        $sql = "INSERT INTO user_tbl (user_id,username,password,f_name,l_name,major_id,cmu_mail,line_id,facebook_link,tel,user_type) VALUES 
(NULL,:username,:pass,:firstname,:lastname,:major,:cmu_mail,:line_id,:facebook_link,:tel,4)";
        $statement = $db->prepare($sql);
        // echo $statement;
        $result = $statement->execute(
            [
                ':username' => $decode['username'],
                ':pass' => password_hash($decode['password'], PASSWORD_BCRYPT, ['cost' => 12]),
                ':firstname' => $decode['f_name'],
                ':lastname' => $decode['l_name'],
                ':major' => $decode['major_id'],
                ':cmu_mail' => $decode['cmu_mail'],
                ':line_id' => isset($decode['line_id']) ? $decode['line_id'] : null,
                ':facebook_link' => isset($decode['facebook_link']) ? $decode['facebook_link'] : null,
                ':tel' => $decode['tel'],
            ]
        );
        http_response_code(200);
        $encode = json_encode($result);
        die($encode);
    } catch (Exception $e) {
        http_response_code(400);
        $message = $e->getMessage();
        if (strpos('23000', $message)) die(json_encode(['error' => 'Username Already Existed']));
        echo json_encode($message);
    }
} else if ($_SERVER['REQUEST_METHOD'] === 'PATCH') {
    $input = file_get_contents('php://input');
    $decode = json_decode($input, true);

    if (
        is_null($decode['cmu_mail'])
        || is_null($decode['f_name'])
        || is_null($decode['l_name'])
        || is_null($decode['tel'])
    ) {
        http_response_code(400);
        echo json_encode(['status' => 'something went wrong']);
        exit(-1);
    }

    try {
        // $sql = "INSERT INTO user_tbl (user_id,username,password,f_name,l_name,major,cmu_mail,line_id,facebook_link,tel,user_type) VALUES 
        // ('',:username,:pass,:firstname,:lastname,:major,:cmu_mail,:line_id,:facebook_link,:tel,4)";


        $sql = "";
        $bindingParams = [];

        if (isset($decode['password'])) {

            $sql = "UPDATE user_tbl SET username=:username,
    f_name=:firstname,
    l_name=:lastname,
    major_id=:major,
    tel=:tel,
    cmu_mail=:cmu_mail,
    line_id=:line_id,
    facebook_link=:facebook_link,
    password = :pass
    WHERE true
    AND user_id = :user_id
";
            //  $bindingParams[':pass'] = password_hash($decode['password'],PASSWORD_BCRYPT,['cost'=>12]);
            $bindingParams =  [
                ':user_id' => $decode['user_id'],
                ':username' => $decode['username'],
                ':firstname' => $decode['f_name'],
                ':lastname' => $decode['l_name'],
                ':major' => $decode['major_id'],
                ':cmu_mail' => $decode['cmu_mail'],
                ':line_id' => isset($decode['line_id']) ? $decode['line_id'] : null,
                ':facebook_link' => isset($decode['facebook_link']) ? $decode['facebook_link'] : null,
                ':tel' => $decode['tel'],
                ':pass' => password_hash($decode['password'], PASSWORD_BCRYPT, ['cost' => 12])

            ];
            // have passowrd change 
        } else {
            $sql = "UPDATE user_tbl SET username=:username,
                            f_name=:firstname,
                            l_name=:lastname,
                            major_id=:major,
                            tel=:tel,
                            cmu_mail=:cmu_mail,
                            line_id=:line_id,
                            facebook_link=:facebook_link
                            WHERE true
                            AND user_id = :user_id
";

            $bindingParams =  [
                ':user_id' => $decode['user_id'],
                ':username' => $decode['username'],
                ':firstname' => $decode['f_name'],
                ':lastname' => $decode['l_name'],
                ':major' => $decode['major_id'],
                ':cmu_mail' => $decode['cmu_mail'],
                ':line_id' => isset($decode['line_id']) ? $decode['line_id'] : null,
                ':facebook_link' => isset($decode['facebook_link']) ? $decode['facebook_link'] : null,
                ':tel' => $decode['tel'],
            ];
            // none passowrd change 

        }

        $statement = $db->prepare($sql);
        $result = $statement->execute(
            $bindingParams
        );
        http_response_code(200);
        $encode = json_encode(['result' => $result]);
        die($encode);
    } catch (Exception $e) {
        http_response_code(400);
        echo json_encode([$e->getMessage()]);
    }
} else if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
    $id = $_GET['id'];
    echo $_SERVER['REQUEST_METHOD'];
    $sql = "UPDATE user_tbl SET 
                deleted=1
                WHERE user_id=:id;";
    $statement = $db->prepare($sql);
    $result = $statement->execute([
        ':id' => $id,
    ]);

    echo json_encode($result);
}
