<?php
namespace Core42\Services;

use Pimple\Container;
use PDO;

class UserService {

    private $c;

    /**
     * UserService, constructed by the container
     *
     * @param Container $container
     */
    public function __construct(Container $container)
    {
        $this->c = $container;
    }

    public function findAll() {
        $handle = $this->c['pdo']->prepare('SELECT * FROM wp_posts WHERE post_status = "publish"');
        $handle->execute();
        return $handle->fetchAll(PDO::FETCH_ASSOC);
    }
}