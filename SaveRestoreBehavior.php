<?php

namespace shirase\returned;

use Yii;
use yii\base\Behavior;
use yii\web\Controller;
use yii\web\View;

/**
 * Class SaveRestoreBehavior
 * @package shirase\returned
 */
class SaveRestoreBehavior extends Behavior {

    /**
     * @var int Number of saved routes
     */
    public $limit = 10;

    private static $sessionKey = 'xyeEdA8Gx8';

    /**
     * @return array
     */
    public function events()
    {
        return [
            Controller::EVENT_BEFORE_ACTION => 'beforeAction'
        ];
    }

    public function beforeAction()
    {
        $route = Yii::$app->controller->route;

        if (Yii::$app->request->get('returned')) {
            if ($routes = Yii::$app->session->get(self::$sessionKey)) {
                if ($data = $routes[$route]) {
                    $_GET = (array)$data['get'];
                    $_POST = (array)$data['post'];
                    $_REQUEST = (array)$data['request'];

                    unset($_GET['returned']);
                    unset($_REQUEST['returned']);

                    if ($data['pathInfo']) {
                        $url = $data['pathInfo'];
                        if($data['queryString']) {
                            $url .= '?'.$data['queryString'];
                        }
                        $js = <<<JS
if(window.history) window.history.replaceState([], "", "{$url}");
JS;
                        Yii::$app->view->registerJs($js, View::POS_HEAD);
                    }
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
            $data = array('get'=>$_GET, 'post'=>$_POST, 'request'=>$_REQUEST, 'pathInfo'=>Yii::$app->request->baseUrl.'/'.Yii::$app->request->pathInfo, 'queryString'=>$this->cleanQueryString(Yii::$app->request->queryString));
            $this->cleanParams($data['get']);
            $this->cleanParams($data['request']);
            $routes[$route] = $data;
            Yii::$app->session->set(self::$sessionKey, $routes);
        }
    }

    private function cleanParams(&$params) {
        if ($params)
        foreach($params as $key=>$val) {
            if($key[0]==='_') {
                unset($params[$key]);
            }
        }
    }

    private function cleanQueryString($queryString) {
        $res = array();
        $params = explode('&', $queryString);
        foreach($params as $param) {
            if($param[0]!=='_') {
                $res[] = $param;
            }
        }
        return implode('&', $res);
    }
} 