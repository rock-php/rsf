<?php

namespace Rsf;

class Rbac {

    const ACL_EVERYONE = 'ACL_EVERYONE';
    const ACL_HAS_ROLE = 'ACL_HAS_ROLE';
    const ACL_NO_ROLE = 'ACL_NO_ROLE';
    const ACL_NULL = 'ACL_NULL';


    /**
     * @param $controllerName
     * @param null $actionName
     * @param string $auth
     * @return bool
     */
    public static function check($controllerName, $actionName = null, $auth = 'general') {
        $_controllerName = strtoupper($controllerName);
        $_actionName = strtolower($actionName);
        $ACT = self::_getACT($_controllerName);
        //if controller offer empty ACT, authtype 'general' then allow
        if ('general' == $auth) {
            if (is_null($ACT) || empty($ACT)) {
                return true;
            }
        } else {
            if (is_null($ACT) || empty($ACT)) {
                return false;
            }
        }
        // get user rolearray
        $roles = Context::getRolesArray();
        // 1, check user's role whether allow to call controller
        if (!self::_check($roles, $ACT)) {
            return false;
        }
        // 2, check user's role whether allow to call action
        if (!is_null($_actionName)) {
            //$actionName = strtoupper($_actionName);
            if (isset($ACT['actions'][$_actionName])) {
                if (!self::_check($roles, $ACT['actions'][$_actionName])) {
                    return false;
                }
            }
        }
        return true;
    }

    /**
     * @param $_roles
     * @param $ACT
     * @return bool
     */
    private static function _check($_roles, $ACT) {
        $roles = array_map('strtoupper', $_roles);
        if ($ACT['allow'] == self::ACL_EVERYONE) {
            //if allow all role ,and deny is't set ,then allow
            if ($ACT['deny'] == self::ACL_NULL) {
                return true;
            }
            //if deny is ACT_NO_ROLE ,then user has role, allow
            if ($ACT['deny'] == self::ACL_NO_ROLE) {
                if (empty($roles)) {
                    return false;
                }
                return true;
            }
            //if deny is ACL_HAS_ROLE ,then user's role is empty , allow
            if ($ACT['deny'] == self::ACL_HAS_ROLE) {
                if (empty($roles)) {
                    return true;
                }
                return false;
            }
            //if deny is ACL_EVERYONE ,then ACT is false
            if ($ACT['deny'] == self::ACL_EVERYONE) {
                return false;
            }
            //if deny has't the role of user's roles , allow
            foreach ($roles as $role) {
                if (in_array($role, $ACT['deny'], true)) {
                    return false;
                }
            }
            return true;
        }

        do {
            //if allow request role , user's role has't the role , deny
            if ($ACT['allow'] == self::ACL_HAS_ROLE) {
                if (!empty($roles)) {
                    break;
                }
                return false;
            }
            //if allow request user's role is empty , but user's role is not empty , deny
            if ($ACT['allow'] == self::ACL_NO_ROLE) {
                if (empty($roles)) {
                    break;
                }
                return false;
            }
            if ($ACT['allow'] != self::ACL_NULL) {
                //if allow request the rolename , then check
                $passed = false;
                foreach ($roles as $role) {
                    if (in_array($role, $ACT['allow'], true)) {
                        $passed = true;
                        break;
                    }
                }
                if (!$passed) {
                    return false;
                }
            }
        } while (false);

        //if deny is't set , allow
        if ($ACT['deny'] == self::ACL_NULL) {
            return true;
        }
        //if deny is ACL_NO_ROEL, user'role is't empty , allow
        if ($ACT['deny'] == self::ACL_NO_ROLE) {
            if (empty($roles)) {
                return false;
            }
            return true;
        }
        //if deny is ACL_HAS_ROLE, user's role is empty ,allow
        if ($ACT['deny'] == self::ACL_HAS_ROLE) {
            if (empty($roles)) {
                return true;
            }
            return false;
        }
        //if deny is ACL_EVERYONE, then deny all
        if ($ACT['deny'] == self::ACL_EVERYONE) {
            return false;
        }
        //only deny hasn't the role of user's role ,allow
        foreach ($roles as $role) {
            if (in_array($role, $ACT['deny'], true)) {
                return false;
            }
        }
        return true;
    }


    /**
     * @param $controllerName
     * @return Array|null
     */
    private static function _getACT($controllerName) {
        // check controller's ACT whether in the globalACT
        $ACT = getcache('globalACT_' . APPKEY . '/' . $controllerName);
        if (is_null($ACT)) {
            $ACT = self::_getControllerACT($controllerName);
        }
        return $ACT;
    }


    /**
     * @param $controllerName
     * @return Array|null
     */
    private static function _getControllerACT($controllerName) {
        $jsonact = include getini('data/_acl') . APPKEY . 'ACT.php';
        if (!$jsonact) {
            setcache('globalACT_' . APPKEY . '/' . $controllerName, ''); //防止反复
            return null;
        }
        $ACT = json_decode($jsonact, true);
        setcache('globalACT_' . APPKEY, $ACT); //保存所有ACT
        if (isset($ACT[$controllerName])) {
            return $ACT[$controllerName];
        } else {
            setcache('globalACT_' . APPKEY . '/' . $controllerName, ''); //防止反复
            return null;
        }
    }

}