<?php
require __DIR__ . '/../vendor/autoload.php';

$templates_path = __DIR__ . "/../tmpl";
$app = new \Slim\App(array(
            "debug" => true,
            "templates.path" => $templates_path
            ));
$container = $app->getContainer();
$container['db'] = function () {
    return new PDO('sqlite:' . __DIR__ . '/../development.db');
    };
// view renderer
$container['renderer'] = function ($c) use ($templates_path) {
    return new Slim\Views\PhpRenderer($templates_path);
};

// 引数で指定した長さのランダムな文字列を生成
function stringRandom($len = 5) {
    if (!is_numeric($len) || $len <= 0) {
        die("positive interger is required.");
    }

    $str = '';
    for ($i = 0; $i < $len; ) {
        $num = mt_rand(0x30, 0x7A);
        if ((0x30 <= $num && $num <= 0x39) || (0x41 <= $num && $num <= 0x5A) ||
                (0x61 <= $num && $num <= 0x7A)) {
            $str .= chr($num);
            $i++;
        }
    }
    return $str;
}

$app->get('/', function ($req, $res, $args) {
        return $this->renderer->render($res, 'index.phtml');
        })->setName('index');

$app->post('/create/', function ($req, $res, $args) {
        $db = $this->db;
        $url = $req->getParam("url");
        if (!$url) {
        return $res->withRedirect('/');
        }

        // dup check
        $sth = $db->prepare('SELECT key FROM tinyurl WHERE url = ? LIMIT 1;');
        $sth->execute(array($url));
        $result = $sth->fetch(PDO::FETCH_ASSOC);
        $key = $result['key'];
        if (!$key) {
        // create new one
        $key = stringRandom(6);
        $sth = $db->prepare('INSERT INTO tinyurl (key, url) VALUES (?, ?);');
        $sth->execute(array($key, $url));
        }
        return $this->renderer->render($res, 'result.phtml', array("tinyurl" => $req->getUri()->getBaseUrl() . '/g/' . $key));
        });

$app->get('/g/{key}', function ($req, $res, $args) {
        $db = $this->db;
        $key = $args['key'];
        if (!$key) {
        return $res->withRedirect('/');
        }

        $sth = $db->prepare('SELECT url FROM tinyurl WHERE key = ? LIMIT 1;');
        $sth->execute(array($key));
        $result = $sth->fetch(PDO::FETCH_ASSOC);
        $url = $result['url'];
        return $res->withRedirect(($url) ? $url : '/');
        });

$app->run();
?>
