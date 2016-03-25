<?php

namespace shirase\returned;

use yii\base\ActionFilter;
use Yii;

/**
 * Class SaveRestoreFilter
 * @package shirase\returned
 */
class SaveRestoreFilter extends ActionFilter {

    /**
     * @var int Number of saved routes
     */
    public $limit = 3;

    private static $sessionKey = 'xyeEdA8Gx8';

    public function beforeAction($action)
    {
        $route = $action->controller->route;

        if (Yii::$app->request->get('returned')) {
            if ($routes = Yii::$app->session->get(self::$sessionKey)) {
                if ($data = $routes[$route]) {
                    $_GET = $data['get'];
                    $_POST = $data['post'];
                    $_REQUEST = $data['request'];
                }
            }
        } else {
            $routes = Yii::$app->session->get(self::$sessionKey, array());
            if ($routes[$route]) {
                unset($routes[$route]);
            } else {
                if(sizeof($routes)>=$this->limit-1) {
                    array_shift($routes);
                }
            }
            $data = array('get'=>$_GET, 'post'=>$_POST, 'request'=>$_REQUEST);
            $routes[$routes] = $data;
            Yii::$app->session->set(self::$sessionKey, $routes);
        }
    }
} 