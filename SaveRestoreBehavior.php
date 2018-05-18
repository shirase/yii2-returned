<?php

namespace shirase\returned;

use Yii;
use yii\base\Behavior;
use yii\helpers\ArrayHelper;
use yii\web\Application;
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
    private static $needSave = true;

    /**
     * @return array
     */
    public function events()
    {
        return [
            Application::EVENT_BEFORE_REQUEST => 'beforeRequest',
            Application::EVENT_BEFORE_ACTION => 'beforeAction',
        ];
    }

    public function beforeAction()
    {
        if (self::$needSave && !Yii::$app->request->get('returned')) {
            $route = Yii::$app->controller->route;
            $routes = Yii::$app->session->get(self::$sessionKey, array());
            if (isset($routes[$route])) {
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

    public function beforeRequest()
    {
        if (isset($_GET['returned'])) {
            unset($_GET['returned']);

            list ($route, $params) = Yii::$app->request->resolve();

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

                        Yii::$app->request->url = $url;
                    }

                    self::$needSave = false;
                }
            }
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
            if(ArrayHelper::getValue($param, 0)!=='_') {
                $res[] = $param;
            }
        }
        return implode('&', $res);
    }
} 