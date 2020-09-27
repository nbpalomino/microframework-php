<?php
namespace Core42\Controllers;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use PDO;

class MainController extends Controller {

    public function index(Request $req, $params) {
        return views('webapp.php', $params);
    }

    public function greet(Request $req, $params) {
        $user_service = $this->container->get('services.user');
        // return JsonResponse::fromJsonString('{"status":200,"message":"hello world"}');
        return new JsonResponse($user_service->findAll());
    }

    public function exception(Request $req, $params) {
        $handle = $this->container->get('pdo')->prepare('SELECT * FROM wp_users');
        $handle->execute();
        $paras = $handle->fetchAll(PDO::FETCH_ASSOC);
        return new JsonResponse($paras);
    }

    public function settings(Request $req) {
        $data = [
            'name' => 'Nick Palomino',
            'today' => date('2020/05/30'),
            'options' => ['age'=>30, 'is_deleted'=>false]
        ];
        return new JsonResponse($data);
    }
}