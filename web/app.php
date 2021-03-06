<?php

if (!file_exists(__DIR__.'/../app/data/install.lock')) {
    header("Location: install/install.php");
    exit();
}

if ((strpos($_SERVER['REQUEST_URI'], '/admin') !== 0) && file_exists(__DIR__.'/../app/data/upgrade.lock')) {
    $time = file_get_contents(__DIR__.'/../app/data/upgrade.lock');
    date_default_timezone_set('Asia/Shanghai');
    $currentTime = time();
    if ($currentTime <= (int) $time) {
        header('Content-Type: text/html; charset=utf-8');
        echo file_get_contents(__DIR__.'/../app/Resources/TwigBundle/views/Exception/upgrade-info.html');
        exit();
    }
}

if ((strpos($_SERVER['REQUEST_URI'], '/api') === 0) || (strpos($_SERVER['REQUEST_URI'], '/app.php/api') === 0)) {
    define('API_ENV', 'prod');
    include __DIR__.'/../api/index.php';
    exit();
}

use Topxia\Service\User\CurrentUser;
use Topxia\Service\Common\ServiceKernel;
use Symfony\Component\HttpFoundation\Request;
use Topxia\Common\AppConnectionFactory;

fix_gpc_magic();

$loader = require_once __DIR__.'/../app/bootstrap.php.cache';

// Use APC for autoloading to improve performance.

// Change 'sf2' to a unique prefix in order to prevent cache key conflicts

// with other applications also using APC.
/*
$loader = new ApcClassLoader('sf2', $loader);
$loader->register(true);
 */

require_once __DIR__.'/../app/AppKernel.php';
//require_once __DIR__.'/../app/AppCache.php';

$kernel = new AppKernel('prod', false);
$kernel->loadClassCache();
//$kernel = new AppCache($kernel);
Request::enableHttpMethodParameterOverride();
$request = Request::createFromGlobals();

$kernel->boot();

// START: init service kernel
$serviceKernel = ServiceKernel::create($kernel->getEnvironment(), $kernel->isDebug());
$serviceKernel->setEnvVariable(array(
    'host'          => $request->getHttpHost(),
    'schemeAndHost' => $request->getSchemeAndHttpHost(),
    'basePath'      => $request->getBasePath(),
    'baseUrl'       => $request->getSchemeAndHttpHost().$request->getBasePath()
));
$serviceKernel->setTranslatorEnabled(true);
$serviceKernel->setTranslator($kernel->getContainer()->get('translator'));
$serviceKernel->setParameterBag($kernel->getContainer()->getParameterBag());
$serviceKernel->registerModuleDirectory(dirname(__DIR__).'/plugins');
$serviceKernel->setConnectionFactory(new AppConnectionFactory());

$currentUser = new CurrentUser();
$currentUser->fromArray(array(
    'id'        => 0,
    'nickname'  => '游客',
    'currentIp' => $request->getClientIp(),
    'roles'     => array()
));
$serviceKernel->setCurrentUser($currentUser);

// END: init service kernel

// NOTICE: 防止请求捕捉失败而做异常处理
// 包括：数据库连接失败等
try {
    $response = $kernel->handle($request);
} catch (\RuntimeException $e) {
    echo "Error!  ".$e->getMessage();
    die();
}

$response->send();
$kernel->terminate($request, $response);

function _fix_gpc_magic(&$item)
{
    if (is_array($item)) {
        array_walk($item, '_fix_gpc_magic');
    } else {
        $item = stripslashes($item);
    }
}

function _fix_gpc_magic_files(&$item, $key)
{
    if ($key != 'tmp_name') {
        if (is_array($item)) {
            array_walk($item, '_fix_gpc_magic_files');
        } else {
            $item = stripslashes($item);
        }
    }
}

function fix_gpc_magic()
{
    if (ini_get('magic_quotes_gpc')) {
        array_walk($_GET, '_fix_gpc_magic');
        array_walk($_POST, '_fix_gpc_magic');
        array_walk($_COOKIE, '_fix_gpc_magic');
        array_walk($_REQUEST, '_fix_gpc_magic');
        array_walk($_FILES, '_fix_gpc_magic_files');
    }
}
