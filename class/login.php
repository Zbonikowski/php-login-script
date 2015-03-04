<?php

class login {
    private $db = null;
    private $hashAddon = 'MyAddon734';
    
    public function __construct($db) {
        $this->db = $db;
    }
    
    public function isLoggedIn() {
        if($_POST['loginBox-submit'] == 'Login') {
            $_SESSION['login'] = $_POST['loginBox-login'];
            $_SESSION['pass'] = md5($_POST['loginBox-pass'] . $this->hashAddon);
        }
        if ( $this->checkLoginInformation() === false ) {
            $ret = false;
        } else {
            $ret = true;
        }
        return $ret;
    }
    
    private function checkLoginInformation(){
        $db = $this->db;
        $return = false;
        $login = $_SESSION['login'];
        $pass = $_SESSION['pass'];

        $q = 'SELECT deleted FROM login_accounts WHERE login = "' . $login . '" AND pass = "' . $pass . '" AND deleted = "F"';
        $db->Query($q) or die($db->GetLastError());
        if ($db->GetNumRows() < 1) {
            session_destroy();
            $return = false;
        } elseif ($db->GetNumRows() > 0) {
            $_SESSION['loggedIn'] = true;
            $return = true;
        }

        return $return;
    }
    
    public function loginBox() {
        $ret = "<form action='' method='POST'>";
        $ret .= "<input type='text' name='loginBox-login' placeholder='Login'/>";
        $ret .= "<input type='password' name='loginBox-pass' placeholder='Password'/>";
        $ret .= "<input type='submit' name='loginBox-submit' value='Login'/>";
        $ret .= "</form>";
        
        return $ret;
    }
    
    public function registerBox() {
        if ($_POST['registerBox-submit'] != 'Register') {
            $ret = "<form action='' method='POST'>";
            $ret .= "<input type='text' name='registerBox-name' value='" . $_POST['registerBox-name'] . "' placeholder='Name' required/>";
            $ret .= "<input type='text' name='registerBox-login' value='" . $_POST['registerBox-login'] . "' placeholder='Login' required/>";
            $ret .= "<input type='password' name='registerBox-pass' id='loginBox-pass' placeholder='Password' required/>";
            $ret .= "<input type='password' name='registerBox-pass2' id='loginBox-pass2' placeholder='Password' required/>";
            $ret .= "<select name='registerBox-level'>";
            $ret .= "<option>10</option>";
            $ret .= "</select>";
            $ret .= "<input type='submit' name='registerBox-submit' value='Register'/>";
            $ret .= "</form>";
        } else {
            $ret = $this->registerUser();
        }
        return $ret;
    }
    
    public function registerUser(){
        $db = $this->db;
        $q = 'SELECT login FROM login_accounts WHERE login = "' . $_POST['registerBox-login'] . '"';
        $db->Query($q) or die($db->GetLastError());
        if ($db->GetNumRows() < 1) {
            if ($_POST['registerBox-pass'] == $_POST['registerBox-pass2']){
                $q = 'INSERT INTO login_accounts SET 
                    login = "' . $_POST['registerBox-login'] . '", 
                    pass = "' . md5($_POST['registerBox-pass'] . $this->hashAddon) . '",
                    name = "' . $_POST['registerBox-name'] . '",
                    level = "' . $_POST['registerBox-level'] . '",
                    deleted = "F"';
                $db->Query($q) or die($db->GetLastError());
                $ret = 'All OK!';
            } else{
                $ret = 'Please retype password';
            }
        } else {
            $ret = 'User alredy exist';
        }
        return $ret;
    }
    
    public function logout() {
        session_destroy();
        return true;
    }
    
    public function _debug() {
        $ret .= "<br/>SESSION<pre>" . print_r($_SESSION, true) . "</pre>";
        $ret .= "POST<pre>" . print_r($_POST, true) . "</pre>";
        $ret .= "GET<pre>" . print_r($_GET, true) . "</pre>";
        $ret .= "SERVER<pre>" . print_r($_SERVER, true) . "</pre>";
        
        return $ret;
    }
    
}
