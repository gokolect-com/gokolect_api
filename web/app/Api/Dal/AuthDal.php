<?php

namespace Gokolect\Api\Dal;
/**
 * The connection class, this file is configured to connect to the database
 * 
 * PHP version 8.1.3
 * 
 * @category Micro_Service_Application
 * @package  Gokolect_API_Service
 * @author   Tamunobarasinipiri Joseph Samuel <josephsamuelw1@gmail.com>
 * @license  MIT license 
 * @link     https://gokolect.test
 */

// require_once "utility.php";

use Firebase\JWT\JWT;
use Gokolect\Api\Utility;
use Gokolect\Data\DataOps;

/**
 * Gokolect Authentication class holds all attributes of the authentication functionalities.
 * Gokolect Authentication class holds all attributes of the authentication functionalities from 
 * sign up to email verification and session management.
 * 
 * PHP Version 8.1.3
 * 
 * @category Micro_Service_Application
 * @package  Gokolect_API_Service
 * @author   Tamunobarasinipiri Joseph Samuel <josephsamuelw1@gmail.com>
 * @license  MIT license 
 * @link     https://gokolect.test
 */
class AuthDal extends DataOps
{
    private static $_input_data;
    private $_host_url = null;
    private $_utility;
    private const BAD_REQUEST = "'HTTP/1.0 400 Bad Request";

    /**
     * Class constructor
     * 
     * @param array $data An array of user input data
     * 
     * @return array
     */
    public function __construct($data = null)
    {        
        if (!is_null($data)) {
            unset($data['action']);
            self::$_input_data = $data;
        }
        die(var_dump($_SERVER));
        $this->_host_url = $_SERVER['HTTP_HOST'];
        // $this->_host_url = $_SERVER['HTTP_ORIGIN']."/myhtmlclass";

        $this->_utility = new Utility();
    }

    /**
     * Sign up method 
     * Creates a user record on the db
     * 
     * @return array
     */
    public function signUp()
    {
        $data = self::$_input_data;
        static::$table = "gk_users_tbl";

        $exists = static::exists(['email'=>$data['email']]);       
             
        if ($data['password'] !== $data['confirm_password']) {
            $response = ['statuscode' => -1, 'status' => 'Password mismatch'];
        } else if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $response = ['statuscode' => -1, 'status' => 'Invalid email address'];
        } else if ($exists) {
            $response = ['statuscode' => -1, 'status' => 'Did you forget your password? this email address is already Signed up'];
        } else {      
            unset($data['confirm_password']);      
            $stringify = base64_encode($data['password']);        
            $password = password_hash($stringify, PASSWORD_DEFAULT);
            
            $fullName = $data['first_name'] . " " . $data['last_name'];
            $edate = date('d m Y H i s');
            
            $details1 = ['user_id'=>$data['email'], 'extra'=>$data['mobile'], 'schema'=>static::$table];
            $details = $data['email'].'_'.$data['mobile'].static::$table;
            $jwt = $this->_utility->generateJWTToken($details1);            
           
            $token = base64_encode($details."_".(string) $jwt);
            
            $message = "<div style='display:flex; width:70%; height:auto; position:relative; box-sizing:border-box; background:#f0f0f0; font-family:sans-serif,arial;'><div style='width: 100%; padding: 10px; background:#2ECC71; margin:0; box-sizing:border-box; display:flex; align-items:center; justify-content:center;'><h1 style='color: #fff; font-weight: bold;'>Gokolect</h1></div>";
            $message.="<section style='padding:2.5rem; display:flex; flex-direction:column; background:#fcfcfc;box-sizing:border-box; width:100%;'><h4 style='color: #4f4f4f; margin:15px 0;'>Hi ".$fullName.",</h4>";
            $message.="<p>You have received this email because you signed up with us on 
            <a href='https://gokolect.com'>Gokolect</a> 
            please click on the button bellow to verify your email and activate your account. </p><br/>";
            
            $message.="<a href='".$this->_host_url."/verify_signup.html?token=".$token."&note=".$edate."' style='padding: 15px; border-radius:15px; width: 200px; background:#EA2027; color:#fff; margin:0 auto; box-sizing:border-box; text-align:center; text-decoration:none; font-size:14px; font-weight:bold; box-shadow:0px 0px 2px rgba(0, 0, 0, 0.5);'>VERIFY</a></section></div>";

            $message.="<hr /><div style='color: #4f4f4f; margin:15px;'><p>If you did not sign up with us, please ignore this email.</p></div>";
            
            $email_subj = "Gokolect Email Verification";
            $full_name = $data['first_name'] . " " . $data['last_name'];
            $notification = (object) array(
                'subject'=>$email_subj,
                'message'=>$message,
                'email'=>$data['email'],
                'name'=>$full_name,
                'sender' => 'josephsamuelw1@zohomail.com',
                'appName' => 'Gokolect Focussed on social Kindness'
            );
            
            $sent_email = $this->_utility->sendEmailNotification($notification); 
            
            if (self::getConnection()) { 
                if ($sent_email) {      
                    $data_array = array(
                        "first_name"=>$data['first_name'],
                        "last_name"=>$data['last_name'],
                        "password"=>$password,
                        "email"=>$data['email'],
                        "mobile"=>$data['mobile']
                    );         
                             
                    $stmt = static::save($data_array);
                   
                    if ($stmt) {
                        $result = self::findOne(['email'=> $data['email']]);
                        if ($result) {
                            $response = ['statuscode' => 0, 'status' => "Sign up successful", 'data' => $result];
                        } else {
                            $response = ['statuscode' => -1, 'status' => "Unknown error" . $result];
                        }
                    } else {
                        $response = ['statuscode' => -1, 'status' => "Sign up failed"];
                    }
                } else {
                    $response = ['statuscode' => -1, 
                    'status' => "Sign up failed " . $sent_email];
                }
            } else {
                $response = ['statuscode' => -1, 'status' => "Connection error"];
            }
        }
        return $response;
    }

    /**
     * Verify user sign up email
     * 
     * @return bool
     */
    public function verifyEmail()
    {
        static::$table = "gk_users_tbl";

        $data = self::$_input_data['token'];

        $item =  explode('_', base64_decode($data)); 
        
        $check_token = $this->_utility->decodeJWTToken($item[4], $item[0]);
        
        if ($check_token->valid) {      
            $result = static::findOne(['email'=> $check_token->userName]);   
            
            if ($result['is_verified']) {
                $response =['statuscode' => -1, 'status' => 'This account had been verified already'];
            } else {
                self::$pk = 'email';
                $update_data = ["is_verified" => 1, "is_locked" => 0, "email" => $check_token->userName];
                $verified = self::update($update_data);
               
                if ($verified) {                    
                    $response =['statuscode' => 0, 'status' => 'Verification successful', 'data'=>$result];
                } else {                    
                    $response =['statuscode' => -1, 'status' => 'Verification failed', 'data'=>$result];
                }
            }
        } else {
            $response =['statuscode' => -1, 'status' => 'Verification failed'];
        }
        return $response;
    }

    /**
     * Sign In method
     * Signs a user in the system by confirming user's 
     * record based on given parameters.
     * 
     * @return array
     */
    public function signIn()
    {       
        $data = self::$_input_data;
        static::$table = "gk_users_tbl";
        static::$pk = "email";
        $stringify = base64_encode($data['password']);
        
        if (self::getConnection()) {
            $fields = ["id"=>"id", "email" => "email", "password" => "password", "first_name" => "first_name", "last_name" => "last_name", "created_at" => "created", "is_locked" => "is_locked", "is_verified" => "is_verified", "mobile" => "mobile"];
            unset($data['password']);
            $result = self::findResults($fields, $data);
            if ($result) {                       
                if (!$result['is_verified']) {
                    $response = ["statuscode" => -1, "status" => "Please verify your email address"];
                } else if (!empty($result['is_locked'])) {
                    $response = ["statuscode" => -1, "status" => "This account is currently locked contact Gokolect Team!"];
                } else {
                    $check_password = password_verify($stringify, $result['password']);                    
                    if ($check_password) {
                        unset($result['password']);
                        unset($result['is_verified']);
                        unset($result['is_locked']);
                        $session = $this->_setSession($result);
                        unset($session['error']);
                        $response = $session;
                    } else {
                        $response = ["statuscode" => -1, "status" =>"Invalid Sign In details"];
                    }
                }
            } else {
                $response = ['statuscode'=>-1, "status" => "Invalid Sign In details"];
            }
        } else {
            $response = ["statuscode" => -1, "status" => "Connection Error"];
        }
        return $response;
    }
    
    /** 
     * Verify User session
     * 
     * @return bool
     */
    public function verifySession()
    {        
        static::$table = "gk_session_tbl";
        static::$pk = "session_id";
        if (! preg_match('/Bearer\s(\S+)/', $_SERVER['HTTP_AUTHORIZATION'], $matches)) {
            exit(header(self::BAD_REQUEST));
            exit(header('Location: /polljota/index.html'));            
        }
        
        $jwt = $matches[1];
        if (empty($matches) || empty($jwt)) {
            exit(header(self::BAD_REQUEST));
            exit(header('Location: /polljota/index.html'));
        }
        $item = explode('_', base64_decode($jwt));
        
        $verify_jwt = $this->_utility->decodeJWTToken($item);
        if ($verify_jwt->valid) {
            $session = $this->_verifySession($verify_jwt->userName, $item[1]);
            if ($session['statuscode'] === 0 && $session['data']['elapsed'] >= 10) {
                $result = $this->_removeSession($verify_jwt->userName, $item[1]);
                $response = ['statuscode' => -1, "status" => $result ." Your Session has expired sign in again"];
            } else if ($session['statuscode'] === 0 && $session['data']['elapsed'] < 30) {
                self::update(
                    ["session_elapsed" => $_SERVER['REQUEST_TIME'], "session_id" => $item[1]] 
                );
                $response = ['statuscode' => 0, "status" => "Session active"];
            } else {
                $response = ['statuscode' => -1, "status" => "You are not signed in"];
                $result = $this->_removeSession($verify_jwt->userName, $item[1]);
            }
        } else {
            $response = ['statuscode'=> -1, "status" => "Invalid session"];            
        }
        return $response;
    }

    /** 
     * Set users session method
     * Sets a log of user's session 
     * 
     * @param array $data session data
     * 
     * @return array
     */
    private function _setSession($data)
    {       
        static::$table = "gt_sessions_tbl";

        if (self::getConnection()) {
            
            session_start();            
            session_regenerate_id();

            $user_ip = $_SERVER['REMOTE_ADDR'];
            $browser = $_SERVER['HTTP_USER_AGENT'];
            $elapsed = $_SERVER['REQUEST_TIME'];
            
            $details1 = ['user_id'=>$data['id'], 'extra'=>session_id(), 'schema'=>self::$table, 'email'=>$data['email']];

            $details = $data['id'].'_'.session_id().'_'.$elapsed;
            $jwt = $this->_utility->generateJWTToken($details1);            
            $token = base64_encode($details."_".(string) $jwt);
                      
            $verify_session = $this->_verifySession($data['id'], session_id()); 
            
            if ($verify_session['statuscode'] == 0) {
                $this->_removeSession($data['id'], session_id());
            }    
            $array_data = [
                "session_id"=>session_id(), 
                "user_id" => $data['id'], 
                "user_ip" => $user_ip, 
                "user_agent" => $browser, 
                "session_time" => $elapsed, 
                "session_status" => 1, 
                "session_elapsed" => $elapsed
            ];

            $result = self::save($array_data);
               
            if ($result) {
                // if ($data['role'] === 'Member') {
                //     $page = 'members/'.strtolower($data['role']).'.html';                
                // } else {
                //     $page = 'electroller/workdesk.html';
                // }
                $response['error'] = false;
                $response['statuscode'] = 0;
                $response['status'] = "Sign In successful!";
                $response['data'] = $token;
                // $response['page'] = $page;
                // $response['data'] = $fields = ['session_id' => session_id(), 'session_user' => $data['email'], 'role' => $data['role']];
            } else {
                unset($response['data']);
                $response = ['error' => true, 'statuscode' => -1, 'status' => 'Unable to Sign you in at the moment'];
            }
        } else {
            $response = ["statuscode" => -1, "status" => "Connection Error"];
        }
        return $response;
    }

    /** 
     * Check time elapsed
     * 
     * @param integer $logged_time session start timestamp
     * 
     * @return bool 
     */
    private function _checkTimeElapsed($logged_time)
    {
        $start = $_SERVER['REQUEST_TIME'];
        $end = $logged_time;        
        $minutes = round(abs($end - $start) / 60, 2);           
        return intval($minutes);
    }

    /**
     * Verify users session
     * Checks if a user's session exists 
     * 
     * @param string $user_id    user email
     * @param string $session_id user's session id
     * 
     * @return bool
     */
    private function _verifySession($user_id, $session_id)
    {   
        static::$table = "gk_sessions_tbl";
        $data = [
            "user_id" => "user_id", "session_id" => "session_id", "session_status" => "session_status", "session_elapsed" => "session_elapsed"
        ];
        $params = ["user_id" => $user_id, "session_id" => $session_id];
        $result = self::findResults($data, $params);                       
        if ($result) {
            $result['elapsed'] = $this->_checkTimeElapsed($result['session_elapsed'], 'm');                
            $response = ['statuscode' => 0, 'status' => 'Exists', 'data' => $result];
        } else {
            $response = ['statuscode' => -1, 'status' => 'false'];
        }
        return $response;
    }

    /**
     * Delete user session
     * Deletes user's session on signout or when user is in active for a while 
     * 
     * @param string $user_id    user email 
     * @param string $session_id seesion id
     * 
     * @return bool
     */
    private function _removeSession($user_id, $session_id)
    {
        static::$table = "gk_sessions_tbl";
        if (self::getConnection()) {
            $data = ['user_id'=>$user_id, 'session_id'=>$session_id];
            $result = self::delete($data);
            $response = $result? true: false;
        } else {
            $response = ['statuscode' => -1, "status" => "Connection Error"];
        }
        return $response;
    }

    /**
     * Sign out a session
     * Sign a user out from a session
     * 
     * @return boolean
     */
    public function signOut()
    {
        static::$table = "gk_users_tbl";
               
        if (! preg_match('/Bearer\s(\S+)/', $_SERVER['HTTP_AUTHORIZATION'], $matches)) {
            exit(header(self::BAD_REQUEST));
            header('Location: /polljota/index.html');
            exit;
        }
                
        $jwt = $matches[1];
        if (empty($matches) || empty($jwt)) {
            exit(header(self::BAD_REQUEST));
            header('Location: /polljota/index.html');
            exit;
        }
        $item = explode('_', base64_decode($jwt));
        
        $verify_jwt = $this->_utility->decodeJWTToken($item[3], $item[0]);

        if ($verify_jwt->valid) {
            $verify_session = $this->_verifySession($verify_jwt->userName, $item[1]);
            if ($verify_session['statuscode'] == 0) {
                $sign_out = $this->_removeSession($verify_jwt->userName, $item[1]);                
                if ($sign_out) {
                    $response = ['statuscode' => 0, 'status'=> 'Sign Out successful'];
                } else {
                    $response = ['statuscode' => -1, 'status' => 'Unable to sign out'];
                }
            } else {                    
                $response = ['statuscode' => -1, 'status' => 'You session had expired'];
            }
        } else {
            $response = ['statuscode' => -1, 'status' => 'Invalid session token'];
        }
        return $response;
    }

    /**
     * Change password 
     * Change default or previous password
     * 
     * @return bool
     */
    public function changePassword()
    {
        static::$table = "gk_users_tbl";
        if (empty($_SERVER['HTTP_AUTHORIZATION']) || ! preg_match('/Bearer\s(\S+)/', $_SERVER['HTTP_AUTHORIZATION'], $matches)) {
            exit(header(self::BAD_REQUEST));           
        }
        
        $jwt = $matches[1];
    
        if (empty($matches) || empty($jwt)) {
            exit(header(self::BAD_REQUEST));
        }
        $item = explode('_', base64_decode($jwt));
        
        $verify_jwt = $this->_utility->decodeJWTToken($item[3], $item[0]);
        $get_maggie = base64_decode($verify_jwt->maggie);

        if (!self::$_input_data['confirm_password'] == self::$_input_data['new_password']) {
            $response = ['statuscode' => -1, 'status' => 'Both passwords do not math'];
            return $response;
        }
        
        $old_password = base64_encode(self::$_input_data['current_password']);
        $new_password = base64_encode(self::$_input_data['new_password']);

        if (self::getConnection()) {
            $data = ["password" => "password"];
            $params = ["email" => $get_maggie];
            $result = self::findResults($data, $params);
            
            if (password_verify($new_password, $result['password'])) {
                $response = ['statuscode' => -1, 'status' => "New password cannot be same as your current password"];
            } else if (password_verify($old_password, $result['password'])) {
                $password = password_hash($new_password, PASSWORD_DEFAULT);
                static::$pk = "email";
                $update = self::update(['email'=> $data['email'], 'password'=>$password]);
                if ($update) {
                    $response = ['statuscode' => 0, "status" => "Password changed successfully"];
                }
            } else {
                $response = ['statuscode' => -1, "status" => "current password is incorrect"];
            }
        } else {
            $response = ['statuscode' => -1, "status" => "Connection error"];
        }
        return $response;
    }

    /**
     * Confirm OTP confirmation code 
     * reset user password on request
     * 
     * @return array
     */
    public function confirmOtpCode()
    {
        static::$table = "gk_users_tbl";
        static::$pk = "confirm_code";

        $confirm_password = base64_encode(self::$_input_data['confirm_password']);
        $password = base64_encode(self::$_input_data['new_password']);
        if (self::getConnection()) {
            if ($confirm_password !== $password) {
                $response = ['statuscode' => -1, 'status' => "Both passwords do not match"];
            } else {
                $password = password_hash($password, PASSWORD_DEFAULT);
                $update = self::update(["confirm_code" =>self::$_input_data['confirm_code'], "password" => $password]);
                if ($update) {
                    $response = ['statuscode' => 0, "status" => "Password reset successful"];
                }
            }
        } else {
            $response = ['statuscode' => -1, "status" => "Connection error"];
        }
        return $response;
    }

    /**
     * Reset password 
     * Reset password if password has been forgotten
     * 
     * @return bool
     */
    public function resetPassword()
    {               
        static::$table = "gk_users_tbl";

        if (!filter_var(self::$_input_data['reset_email'], FILTER_VALIDATE_EMAIL)) {
            $response = ['statuscode'=>-1,'status'=>'Email is invalid'];
        } else {
            $confirm_code =rand(1, 999).rand(0, 999);
            static::$pk = "email";
            
            $set_code = self::update(
                ["confirm_code" => $confirm_code, "email" =>self::$_input_data['reset_email']]
            );        
            if ($set_code) {                
                $result = self::findOne(["email" => self::$_input_data['reset_email'], "confirm_code" =>$confirm_code]);

                $arrDetails = (object) $result;
                $message = "<div style='width:75%; height:auto; box-sizing:border-box; margin: 0 auto; position:relative;'>";
                $message.="<header style='width: 100%; height:auto; padding: 15px; background:#2ECC71; margin-bottom: 25px;'><h1 style='color: #fff; font-weight: bolder;'>Gokolect|<small>Focussed on social kindness</small></h1></header>";                
                
                $message.="You have received this mail because you requested for a password reset on 
                <a href='".$this->_host_url."'>Gokolect</a> Use the One Time Code (OTC) to reset your password when prompted to enter confirmation code.";
                $message.="<h3>One Time Code: <span style='font-size:20px; font-weight:bolder;'>".$confirm_code."</span></h3>";
                $message.= ". <p>If you did not initiate this process, please kindly ignore this message or change your password.</p></div>";                                
                    
                $email_subject = "Password Reset";
                $full_name = $arrDetails->first_name.' '.$arrDetails->last_name;
                
                $notification = (object) array(
                    'subject'=>$email_subject,
                    'message'=>$message,
                    'email'=>$arrDetails->email,
                    'name'=>$full_name,
                    'sender' => 'josephsamuelw1@zohomail.com',
                    'appName' => 'Gokolect focussed on social kindness'
                );
                $sent_email = $this->_utility->sendEmailNotification($notification);
                
                if ($sent_email) {                
                    $response = ['statuscode' => 0, 'status' => "A password reset link has been sent to your email inbox, use that code to reset your password"];
                } else {
                    $response = ['statuscode' => -1, "status" => "Unable to reset your password at the moment"];
                }            
            } else {
                $response = ['statuscode'=>-1, 'status'=>'There was an error processing your request. Please try again later'];
            }
        }
        return $response;
    }

    /**
     * Delete an account
     * Delete user account on request using the users's email address required
     * 
     * @return bool   
     */
    public function deleteAccount()
    {
        static::$table = "gk_users_tbl";
        static::$pk = "email";
        
        if (empty($_SERVER['HTTP_AUTHORIZATION']) || ! preg_match('/Bearer\s(\S+)/', $_SERVER['HTTP_AUTHORIZATION'], $matches)) {
            exit(header(self::BAD_REQUEST));           
        }
        
        $jwt = $matches[1];
    
        if (empty($matches) || empty($jwt)) {
            exit(header(self::BAD_REQUEST));
        }
        $item = explode('_', base64_decode($jwt));
        
        $verify_jwt = $this->_utility->decodeJWTToken($item[3], $item[0]);
        $get_maggie = base64_decode($verify_jwt->maggie);

        if (self::getConnection()) {
            if (self::exists(['email', $get_maggie])) {
                $result = self::delete(['email' => $get_maggie]);
                if ($result) {
                    $response = ['statuscode' => 0, 'status' => "User account deleted successfully"];
                } else {
                    $response = ['statuscode' => 1, 'status' => "Cannot delete this user account!"];
                }
            } else {
                $response = ['statuscode' => -1, 'status' => "Unknown user account!"];
            }
        } else {
            $response = ['statuscode' => -1, 'status' => "Connection error!"];
        }
        return $response;
    }

    /**
     * Lock user account 
     * Locks a user account on request using user email 
     * 
     * @return array   
     */
    public function lockUser()
    {
        static::$table = "gk_users_tbl";
        static::$pk = "email";

        if (empty($_SERVER['HTTP_AUTHORIZATION']) || ! preg_match('/Bearer\s(\S+)/', $_SERVER['HTTP_AUTHORIZATION'], $matches)) {
            exit(header(self::BAD_REQUEST));           
        }
        
        $jwt = $matches[1];
    
        if (empty($matches) || empty($jwt)) {
            exit(header(self::BAD_REQUEST));
        }
        $item = explode('_', base64_decode($jwt));
        
        $verify_jwt = $this->_utility->decodeJWTToken($item[3], $item[0]);
        $get_maggie = base64_decode($verify_jwt->maggie);

        if (self::getConnection()) {
            if (self::exists(['email'=> $get_maggie])) {
                if (!$this->_isLocked($get_maggie)) {
                    $result = self::update(
                        ['is_locked'=> true, 'email'=>$get_maggie]
                    );
                    if ($result) {
                        $response = ['statuscode' => 0, 'status' => "This user account has been locked now!"];
                    } else {
                        $response = ['statuscode' => -1, 'status' =>"Unable to lock user account at the moment"];
                    }
                } else {
                    $response = ['statuscode' => -1, 'status' => "User account was locked already!"];
                }
            } else {
                $response = ['statuscode' => -1, 'status' => "Unkown user account!"];
            }
        } else {
            $response = ['statuscode' => -1, 'status' => "Connection error"];
        }
        return $response;
    }

    /**
     * Is locked 
     * Checks if a user's account is locked or not
     * 
     * @param string $user_id the user email to check with 
     * 
     * @return bool
     */
    private function _isLocked($user_id)
    {
        static::$table = "gk_users_tbl";
        $data = ['is_locked' => "is_locked"];
        $params = ['email' => $user_id];
        $result = self::findResults($data, $params);
        if ($result) {        
            $response = $result['is_locked']? true : false;
        }
        return $response;
    }
}