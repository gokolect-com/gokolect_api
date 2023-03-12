<?php

namespace Gokolect\Api;
/** 
 * Utility class that holds utility functionalities
 * PHP Version 8.1.3
 * 
 * @category MicroService_API_Application
 * @package  Gokolect_API_Service
 * @author   Tamunobarasinipiri Samuel Joseph <josephsamuelw1@gmail.com>
 * @license  MIT License
 * @link     https://gokolect.com
 */

use Nowakowskir\JWT\JWT;
use Nowakowskir\JWT\Exceptions;
use Nowakowskir\JWT\TokenDecoded;
use Nowakowskir\JWT\TokenEncoded;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\OAuth;
/** 
 * Utility class that holds utility functionalities
 * PHP Version 8.1.3
 * 
 * @category MicroService_API_Application
 * @package  Gokolect_API_Service
 * @author   Tamunobarasinipiri Samuel Joseph <josephsamuelw1@gmail.com>
 * @license  MIT License
 * @link     https://gokolect.com
 */
class Utility
{
    var $mail;
    private static $_JWTClass;
    /**
     * The constructor
     */
    public function __construct()
    {
        self::$_JWTClass = new JWT();
    }

    /**
     * Email notification method
     * Sends email notification to a given email address
     * 
     * @param object $data an object of parameters
     * 
     * @return mixed
     */
    public function sendEmailNotification($data)
    {
        $mail = new PHPMailer(true);
        $response = "";
        try {
            // $mail->SMTPDebug = SMTP::DEBUG_SERVER;                      //Enable verbose debug output
            $mail->SMTPDebug = 0;                      //Enable verbose debug output
            $mail->isSMTP();   
            $mail->Mailer = 'smtp';
            $mail->SMTPOptions = array(
                'ssl' => array(
                'verify_peer' => true,
                'verify_peer_name' => true,
                'allow_self_signed' => true
                )
            );                                         //Send using SMTP
            // $mail->Host = 'smtp.hostinger.com';                     //Set the SMTP server to send through
            $mail->Host = 'ssl://smtp.titan.email';                     //Set the SMTP server to send through
            $mail->SMTPAuth = true;                                   //Enable SMTP authentication
            $mail->Username = 'gokolect_info@bootqlass.com';                     //SMTP username
            $mail->Password = 'jos57atg@0806';                               //SMTP password
            // $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTP;            //Enable implicit TLS encryption
            $mail->SMTPSecure = "ssl";            //Enable implicit TLS encryption
            $mail->Port = 465;                                    //TCP port to connect to; use 587 if you have set `SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS`           

            //Recipients
            $mail->setFrom($data->sender, $data->appName);
            $mail->addAddress($data->email, $data->name);     //Add a recipient
            $mail->addAddress($data->email);               //Name is optional
            $mail->addReplyTo('gokolect_info@bootqlass.com', 'Notification');
            // $mail->addCC('cc@example.com');
            // $mail->addBCC('bcc@example.com');

            //Attachments
            // $mail->addAttachment('/var/tmp/file.tar.gz');         //Add attachments
            // $mail->addAttachment('/tmp/image.jpg', 'new.jpg');    //Optional name

            //Content
            $mail->isHTML(true);                                  //Set email format to HTML
            $mail->Subject = $data->subject;
            $mail->Body    = $data->message;
            $mail->AltBody = '';

            $response =$mail->send();
            
        } catch (Exception $exc) {
            $response = $exc;
        }
        return json_encode($response);
    }

    /**
     * Check directory for upload
     * 
     * @param string $dir the path to the directory.
     * 
     * @return string
     */
    private static function _dirt($dir)
    {
        $new_dir = str_replace(' ', '', $dir);
        $applpicsdir = 'http://gokolecttest.bootqlass.com/server/';

        if (!is_dir($applpicsdir)) {
            $applpicsdir = "file_server/";
        } else {
            $applpicsdir = (is_link($applpicsdir)?readlink($applpicsdir):$applpicsdir)."/{$_SERVER['HTTP_HOST']}/{$new_dir}";
        }

        $user_dir = self::_getRelativePath("$applpicsdir/");

        return $user_dir = (is_link($user_dir)?readlink($user_dir):$user_dir);
    }

    /**
     * Upload team and palyer images
     * 
     * @param object $data Object of data parsed
     * @param array  $file Object file details
     * @param string $dir  directory name to upload image
     * 
     * @return mix
     */
    public static function uploadItems($data, $file, $dir):array
    {
        $response = null;
        $dt = strtotime(date('Ymd'));
        $target_dirt = self::_dirt($dir);
        $target_dir = strtolower($target_dirt.DIRECTORY_SEPARATOR.str_replace(' ', '', $dir).DIRECTORY_SEPARATOR);
        $uploadOk = null;
        $imageFileType = explode("/", $file['item_image']["type"]);
        $filename = str_replace(' ', '', strtolower($dt."_".$data['item_code'])).".".$imageFileType[1];
        $target_file = $target_dir."/". str_replace(' ', '', strtolower($dt."_".$data['item_code'])).".".$imageFileType[1];
        $check = explode("/", $file['item_image']["type"]);
        
        if (!is_dir($target_dir)) {
            mkdir($target_dir, 0777, true); 
        } 
        
        if (empty($file['profile_photo']["tmp_name"]) || $file['profile_photo']["error"] > 0 ) {
            $uploadOk = ['status' => 'Please Select an item image to upload.', 'statuscode' => -1];
        } else {
            $uploadOk = 1;
        }

        if ($check[0] === "image") {
            $uploadOk = 1;
        } else {
            $uploadOk = ['status' => "The item image must be an image", 'statuscode' => -2];
        }
        
        if ($file['item_image']["size"] <= 2000000) {
            $uploadOk = 1;
        } else {
            $uploadOk = ['status' => 'The item image you is too large', 'statuscode' => -3];
        }
        
        $formats = ['JPEG', 'JPG', 'jpg', 'jpeg', 'png', 'PNG'];
        
        if (!in_array($imageFileType[1], $formats)) {
            $uploadOk = ['status' => "The item image must be a PNG or JPEG image", 'statuscode' => -4];
        }

        if ($uploadOk != 1) {
            $response = ['status' => $uploadOk['status'], 'statuscode' => $uploadOk['statuscode']];
        } else {
            
            $_FILE = new  \CURLFile(
                $file['item_image']['tmp_name'], 
                $file['item_image']['type'], 
                $file['item_image']['name']
            );

            $result = (array) self::uploadToServer($_FILE, $data['item_code'], $dir, $imageFileType, "items");            
            if ($result['statuscode'] == 200) {
                $response = [
                    'status' => $result['status'], 
                    'statuscode' => $result['statuscode'], 
                    'filename' =>$result['filename'], 
                    'target_dir' => $dir
                ];
            } else {
                $response = [
                    'status' => "upload failed " . $result['status'], 
                    'statuscode' => -1
                ];
            }
        }
        return $response;        
    }

    /**
     * Upload team and palyer images
     * 
     * @param object $data Object of data parsed
     * @param array  $file Object file details
     * @param string $dir  directory name to upload image
     * 
     * @return mix
     */
    public static function uploadImg($data, $file, $dir)
    {                       
        $target_dirt = self::_dirt($dir);
        $dt = strtotime('now');
        $target_dir = strtolower($target_dirt.DIRECTORY_SEPARATOR.strtolower(str_replace(' ', '', $dir)). DIRECTORY_SEPARATOR);
        $uploadOk = 1;
        $imageFileType = explode("/", $file['profile_photo']["type"]);
        die(var_dump($data, $file, $dir));
        $name = uniqid("gkpf")."_".$dt;
        $filename = str_replace(' ', '', $name).".".$imageFileType[1];
        $target_file = strtolower($target_dir . str_replace(' ', '', $name).".".$imageFileType[1]);
        $check = explode("/", $file['profile_photo']["type"]);
        if (!is_dir($target_dir)) {
            mkdir($target_dir, 0777, true); 
        } 

        if (empty($file['profile_photo']["tmp_name"]) || $file['profile_photo']["error"] > 0 ) {
            $uploadOk = ['status' => 'Please Select a profile image to upload.', 'statuscode' => -1];
        } else {
            $uploadOk = 1;
        }
        
        if ($check[0] === "image") {
            $uploadOk = 1;
        } else {
            $uploadOk = ['status' => "The image must be an image", 'statuscode' => -2];
        }
        
        if (file_exists($target_file)) {
            $uploadOk = 1;
        }
        
        if ($file['profile_photo']["size"] <= 2000000) {
            $uploadOk = 1;
        } else {
            $uploadOk = ['status' => 'The image you is too large', 'statuscode' => -3];
        }
        
        $formats = ['JPEG', 'JPG', 'jpg', 'jpeg', 'png', 'PNG'];
        
        if (!in_array($imageFileType[1], $formats)) {
            $uploadOk = ['status' => "The item image must be a PNG or JPEG image", 'statuscode' => -4];
        }

        if (strtolower($imageFileType[1]) != "jpg" && strtolower($imageFileType[1]) != "png" && strtolower($imageFileType[1] != "jpeg")) {
            $uploadOk = 3;
        }

        if ($uploadOk != 1) {
            $response = ['status' => true, 'statuscode' => $uploadOk];
        } else {

            $_FILE = new  \CURLFile(
                $file['profile_photo']['tmp_name'], 
                $file['profile_photo']['type'], 
                $file['profile_photo']['name']
            );

            $result = self::uploadToServer($_FILE, $data['id'], $dir, $imageFileType, "profile");
            
            if ($result) {
                $response = [
                    'status' => $result['status'], 
                    'statuscode' => $result['statuscode'], 
                    'filename' =>$result['filename'], 
                    'target_dir' => $dir
                ];
            } else {
                $response = [
                    'status' => "Unable to upload the image " . $result['status'],  
                    'statuscode' => -1
                ];   
            }
        }
        return $response;        
    }
    
    /**
     * Update player and add player to team
     * 
     * @return bool
     */
    public function getUpdatedPlayers()
    {
        $db = new Connection("mysql"); 
        $json = array();
        $stmt = $db->qry("SELECT s.playerid, s.teamid, p.*, (SELECT teamName FROM team WHERE teamid = s.teamid) AS teamName FROM selections s, players p WHERE p.teamId = s.teamid AND p.pId = s.playerid AND DATE(updated_at) >= CURDATE() ORDER BY p.updated_at DESC LIMIT 1");
        if ($stmt->num_rows > 0) {
            $json = $stmt->fetch_assoc();  
            $file = $this->getuploadedImages("playersphotos", $json['photo']);
            $json['file'] = $file['photo']; 
            return $json;
        }
        return false;   
    }

    /**
     * Upload to server method.
     * Uploads file to server.
     * 
     * @param object $file          the file to upload.
     * @param array  $data          data containing file details.
     * @param string $dir           the directory to place the file.
     * @param array  $imageFileType the type of file.
     * 
     * @return mix
     */
    public static function uploadToServer(object $file, string $data, string $dir, array $imageFileType, String $action)
    {
        $curl = curl_init();
        $remoteData = array(
            'data' => $data,
            'dir' => $dir,
            'imageFileType' => $imageFileType[1],
            'action' => $action,
            'file' => $file
        );
     
        curl_setopt($curl, CURLOPT_URL, "https://gokolecttest.bootqlass.com/server/");
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 1);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 1);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $remoteData);

        $response = (array) json_decode(curl_exec($curl));
        curl_close($curl); 
        
        if (!$curl) {
            $response = ['statuscode' -1, 'status' => curl_error($curl) . curl_errno($curl)];
        }
        return $response;
    } 

    /**
     * Get uploaded images
     * 
     * @param string $dir     the file directory
     * @param string $theFile the name of the file
     * 
     * @return string
     */
    public static function getUploadedImagesFromServer($dir, $theFile) 
    {                     
        $url = "https://gokolecttest.bootqlass.com/server/file_server$dir/$theFile";
        
        if (empty(@file_get_contents($url)) || @file_get_contents($url) === false) {
            $response = ['statuscode' => 404, 'photo' => "N/A "];
        } else {
            $imageFile = @file_get_contents($url);
            $response = ['statuscode' => 200, 'photo' => base64_encode($imageFile)];
        }        
        return $response;
    }

    /**
     * Get uploaded images
     * 
     * @param string $dir     image directory
     * @param string $theFile image
     * 
     * @return string
     */
    public static function getUploadedImages($dir, $theFile) 
    {      
        // $applpicsdir = self::_dirt($dir);
        // $dirt = $applpicsdir;
        // $retval = [];
        // if (substr($dirt, -1) != "/") {
        //     $dirt .= "/";
        // } 
        $dirt = "https://gokolecttest.bootqlass.com/files/file_server/$dir";
        
        // if (is_dir($dirt.=$dir)) {                            
        if (is_dir($dirt)) {                            
            if ($handle = opendir($dirt)) {
                // die(var_dump($dirt."/".$theFile));
                while (false !== ($file = readdir($handle))) {                
                    if ($file !== "." && $file !== "..") {
                        $imgfile = file_get_contents($dirt."/".$theFile);
                        $retval['photo'] = base64_encode($imgfile);         
                    } else {
                        $retval['photo'] = null;
                    }
                }            
                closedir($handle);
            } else {
                $retval['photo'] = null;
            }
        } else {
            $retval['photo'] = null;
        }
        return $retval;
    }

    /**
     * Delete uploaded images
     * 
     * @param string $dir     image directory
     * @param string $thefile image
     * 
     * @return string
     */
    public static function deleteUploadedImages($dir, $thefile) 
    {      
        $applpicsdir = self::_dirt($dir);
        $dirt = $applpicsdir;
        $retval = "";
        if (substr($dirt, -1) != "/") {
            $dirt .= "/";
        }
        if (is_dir($dirt.=$dir) && $handle = opendir($dirt)) {
            while (false !== ($file = readdir($handle))) {                
                if ($file !== "." && $file !== "..") {
                    $imgfile = $dirt."/".$thefile;                    
                    if (file_exists($imgfile)) {
                        $retval = unlink($imgfile);
                    } else {
                        $retval = 10;
                    }       
                }
            }            
            closedir($handle); 
        }
        return $retval;
    }

    /**
     * Get relative path of address
     * 
     * @param string $path a string of directory path or url
     * 
     * @return string
     */
    private static function _getRelativePath($path) 
    {
        $path = preg_replace("#/+\.?/+#", "/", str_replace("\\", "/", $path));
        $dirs = explode("/", rtrim(preg_replace('#^(\./)+#', '', $path), '/'));

        $offset = 0;
        $sub = 0;
        $subOffset = 0;
        $root = "";

        if (empty($dirs[0])) {
            $root = "/";
            $dirs = array_splice($dirs, 1);
        } else if (preg_match("#[A-Za-z]:#", $dirs[0])) {
            $root = strtoupper($dirs[0]) . "/";
            $dirs = array_splice($dirs, 1);
        }

        $newDirs = array();
        foreach ($dirs as $dir) {
            if ($dir !== "..") {
                $subOffset--;
                $newDirs[++$offset] = $dir;
            } else {
                $subOffset++;
                if (--$offset < 0) {
                    $offset = 0;
                    if ($subOffset > $sub) {
                        $sub++;
                    }
                }
            }
        }

        if (empty($root)) {
            $root = str_repeat("../", $sub);
        }
        return $root . implode("/", array_slice($newDirs, 0, $offset));
    }

    /**
     * Finds the schema method.
     * 
     * Finds and returns the schema an object's data is stored in.
     * 
     * @param string $fields   a string of fields separated with ',' 
     *                         to be returned when found
     * @param string $params   a string of parameters parsed based on set criteria
     * @param string $criteria a string of criteria to be used in the query
     * @param object $db       a database connection Object
     * 
     * @return string
     */
    public static function find(String $fields, String $params, String $criteria, Object $db)
    {
        $schema = ['admins', 'members'];
        $table = null;
        foreach ($schema as $field) {
            $result = $db->qry("SELECT {$fields} FROM {$field} WHERE {$criteria} = '?'", $params);
            if ($result->num_rows > 0) {
                $table = $field;
                break;
            }
        }        
        return $table;
    }

    
    /**
     * Generate jwt token
     * Generates a jwt token based on given parameters
     *
     * @param array $data the data to generate token
     *
     * @return object
     */
    public function generateJWTToken(array $data = null)
    {
        $request_data = JWT_DATA;
        if (!is_null($data)) {
            $request_data['userName'] = $data['user_id'];
            $request_data['maggie'] = base64_encode($data['user_id']);
        }
        $jwt = new TokenDecoded($request_data);
        $tokenEncoded = $jwt->encode(PRIVATE_KEY, JWT::ALGORITHM_RS256);
        return $tokenEncoded->toString();
    }

    /**
     * Decode jwt token
     * Decodes a jwt token based on received jwt token from client's request
     *
     * @param array $token   the data to generate token.
     * @param array $user_id the data to generate token.
     *
     * @return object
     */
    public function decodeJWTToken(string $token = null, string $user_id = null)
    {
        $jwt = json_encode($token);
        $tokenEncoded = new TokenEncoded($jwt);
        
        $serverName = rawurldecode(parse_url($_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'], PHP_URL_PATH));
        // $leeway = 500;
        // $tokenEncoded->validate(PUBLIC_KEY, JWT::ALGORITHM_RS256, $leeway);
        try {
       
            $token = (object) $tokenEncoded->decode()->getPayload();
            
            if ($token->iss === $serverName
                && $token->nbf === $token->iat
                && $token->exp > $token->iat
                && $token->userName == $user_id
            ) {
                $token->valid = true;
                $response = $token;
            } else {
                $token->valid = false;
                $response = $token;
            }
        } catch (Exception $e) {
            $response = $e->getMessage();
        }
        return $response;
    }
}