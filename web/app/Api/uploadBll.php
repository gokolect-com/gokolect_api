<?php
/**
 * Simple Google Cloud Storage class
 * by SAK
 */

namespace Curently\Api;
/**
 * PollJota Administration class holds all 
 * attributes of administration functionalities
 * 
 * PHP Version 8.1.3
 * 
 * @category Web_Application
 * @package  PollJotaAPI
 * @author   Tamunobarasinipiri Samuel Joseph <joseph.samuel@cinfores.com>
 * @license  MIT License
 * @link     https://PollJota.idea.cinfores.com
 */

use Nowakowskir\JWT\JWT;
use Nowakowskir\JWT\Exceptions;
use Nowakowskir\JWT\TokenDecoded;
use Nowakowskir\JWT\TokenEncoded;
/**
 * Upload Class
 * A class to help upload files to a google cloud storage.
 * 
 * PHP Version 8.1.3
 * 
 * @category Web_Application
 * @package  PollJotaAPI
 * @author   Tamunobarasinipiri Samuel Joseph <joseph.samuel@cinfores.com>
 * @license  MIT License
 * @link     https://PollJota.idea.cinfores.com
 */
class UploadBll
{
    const 
        GCS_OAUTH2_BASE_URL = 'https://oauth2.googleapis.com/token',
        GCS_STORAGE_BASE_URL = "https://storage.googleapis.com/storage/v1/b",
        GCS_STORAGE_BASE_URL_UPLOAD = "https://storage.googleapis.com/upload/storage/v1/b";

    protected $access_token = null;
    protected $bucket = null;
    protected $scope = 'https://www.googleapis.com/auth/devstorage.read_write';
    

    /**
     * Class constructor
     * 
     * @param string $gservice_account the google account.
     * @param string $private_key      the private key to access account.
     * @param string $bucket           the bucket.
     * 
     * @return mix
     */
    function __construct($gservice_account, $private_key, $bucket) 
    {
        $this->bucket = $bucket;
        // make the JWT
        $iat = time();
        $payload = array(
            "iss" => $gservice_account,
            "scope" => $this->scope,
            "aud" => self::GCS_OAUTH2_BASE_URL,
            "iat" => $iat,
            "exp" => $iat + 3600
        );

        $jwt = new TokenDecoded($payload);
        $tokenEncoded = $jwt->encode(PRIVATE_KEY, JWT::ALGORITHM_RS256);
        // $jwt = JWT::encode(PRIVATE_KEY, JWT::ALGORITHM_RS256);
        // echo "Encode:\n" . print_r($jwt, true) . "\n"; exit;

        $headers = array(
            "Content-Type: application/x-www-form-urlencoded"
        );

        $post_fields = "grant_type=urn%3Aietf%3Aparams%3Aoauth%3Agrant-type%3Ajwt-bearer&assertion=$tokenEncoded";
        // $post_fields = array(
        //     'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
        //     'assertion' => $jwt
        // );

        $curl_opts = array(
            CURLOPT_URL => self::GCS_OAUTH2_BASE_URL,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_POSTFIELDS => $post_fields,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1
        );

        $curl = curl_init();
        curl_setopt_array($curl, $curl_opts);
    
        // var_dump($curl); exit;
        $response = curl_exec($curl);
        if (curl_errno($curl)) {
            die('Error:' . curl_error($curl));
        }
        curl_close($curl);
        $response = json_decode($response, true);
        $this->access_token = $response['access_token'];
        // echo "Resp:\n" . print_r($response, true) . "\n"; exit;
    }

    /**
     * Upload Object method.
     * This method uploads the object to the server.
     * 
     * @param string $file_local_full  the local file path.
     * @param string $file_remote_full the remote file path to upload to.
     * @param string $content_type     the expected content type.
     * 
     * @return mix
     */
    public function uploadObject($file_local_full, $file_remote_full, $content_type = 'application/octet-stream')
    {
        $url = self::GCS_STORAGE_BASE_URL_UPLOAD."/$this->bucket/o?uploadType=media&name=$file_remote_full";
    
        if (!file_exists($file_local_full)) {
            throw new \Exception("$file_local_full not found.");
        }

        // $filesize = filesize($file_local_full);

        $headers = array(
            "Authorization: Bearer $this->access_token",
            "Content-Type: $content_type",
            // "Content-Length: $filesize"
        );

        // if the file is too big, it should be streamed
        $curl_opts = array(
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_POSTFIELDS => file_get_contents($file_local_full),
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1
        );
        // echo "curl_opts:\n" . print_r($curl_opts, true) . "\n"; exit;

        $curl = curl_init();
        curl_setopt_array($curl, $curl_opts);
    
        $response = curl_exec($curl);

        if (curl_errno($curl)) {
            $error_msg = curl_error($curl);
            throw new \Exception($error_msg);
        }

        curl_close($curl);

        return $response;
    }

    /**
     * Upload data method.
     * Uploads data to the storage area.
     * 
     * @param string $data             the data.
     * @param string $file_remote_full the remote file path.
     * @param string $content_type     the expected file content.
     * 
     * @return mix
     */
    public function uploadData(string $data, string $file_remote_full, string $content_type = 'application/octet-stream')
    {
        $url = self::GCS_STORAGE_BASE_URL_UPLOAD."/$this->bucket/o?uploadType=media&name=$file_remote_full";
    
        // $filesize = strlen($data);

        $headers = array(
            "Authorization: Bearer $this->access_token",
            "Content-Type: $content_type",
            // "Content-Length: $filesize"
        );

        $curl_opts = array(
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_POSTFIELDS => $data,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1
        );
        // echo "curl_opts:\n" . print_r($curl_opts, true) . "\n"; exit;

        $curl = curl_init();
        curl_setopt_array($curl, $curl_opts);
    
        $response = curl_exec($curl);

        if (curl_errno($curl)) {
            $error_msg = curl_error($curl);
            throw new \Exception($error_msg);
        }

        curl_close($curl);

        return $response;
    }

    /**
     * Copy object method.
     * Copies an uploaded file based on given source to a given destination.
     * 
     * @param string $from the current location of the file.
     * @param string $to   the destination location.
     * 
     * @return mix
     */
    public function copyObject($from, $to)
    {
        // 'https://storage.googleapis.com/storage/v1/b/[SOURCEBUCKET]/o/[SOURCEOBJECT]/copyTo/b/[DESTINATIONBUCKET]/o/[DESTINATIONOBJECT]?key=[YOUR_API_KEY]'
        $from = rawurlencode($from);
        $to = rawurlencode($to);
        $url = self::GCS_STORAGE_BASE_URL."/$this->bucket/o/$from/copyTo/b/$this->bucket/o/$to";
        // $url = rawurlencode($url);
        
        $headers = array(
            "Authorization: Bearer $this->access_token",
            "Accept: application/json",
            "Content-Type: application/json"
        );
    
        $payload = '{}';
    
        $curl_opts = array(
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_POSTFIELDS => $payload,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1
        );
        // echo "curl_opts:\n" . print_r($curl_opts, true) . "\n"; exit;

        $curl = curl_init();
        curl_setopt_array($curl, $curl_opts);
    
        $response = curl_exec($curl);
        // echo '<pre>'; var_dump($response); exit;
        if (curl_errno($curl)) {
            $error_msg = curl_error($curl);
            throw new \Exception($error_msg);
        }

        curl_close($curl);

        return $response;
    }

    /**
     * Delete uploaded file method.
     * Deletes an uploaded file based on given file name.
     * 
     * @param string $name File name
     * 
     * @return mix
     */
    public function deleteObject($name)
    {
        // curl -X DELETE -H "Authorization: Bearer OAUTH2_TOKEN" "https://storage.googleapis.com/storage/v1/b/BUCKET_NAME/o/OBJECT_NAME"
        //
        $name = rawurlencode($name);
        $url = self::GCS_STORAGE_BASE_URL."/$this->bucket/o/$name";
        
        $headers = array(
            "Authorization: Bearer $this->access_token",
            "Accept: application/json",
            "Content-Type: application/json"
        );
    
        $curl_opts = array(
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST => "DELETE",
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1
        );
        // echo "curl_opts:\n" . print_r($curl_opts, true) . "\n"; exit;

        $curl = curl_init();
        curl_setopt_array($curl, $curl_opts);
    
        $response = curl_exec($curl);
        // echo '<pre>'; var_dump($response); exit;
        if (curl_errno($curl)) {
            $error_msg = curl_error($curl);
            throw new \Exception($error_msg);
        }

        curl_close($curl);

        return $response;
    }


    /**
     * Fetch uploaded file method.
     * Fetches an uploaded file or files based on given name or directory.
     * 
     * @param string $folder the file location or folder.
     * 
     * @return mix
     */
    public function listObjects($folder)
    {
        // curl -X GET -H "Authorization: Bearer OAUTH2_TOKEN" "https://storage.googleapis.com/storage/v1/b/BUCKET_NAME/o"
        //
        $folder = rawurlencode($folder);
        $url = self::GCS_STORAGE_BASE_URL."/$this->bucket/o?prefix=$folder";
        
        $headers = array(
            "Authorization: Bearer $this->access_token",
            "Accept: application/json",
            "Content-Type: application/json"
        );
    
        $curl_opts = array(
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST => "GET",
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1
        );
        // echo "curl_opts:\n" . print_r($curl_opts, true) . "\n"; exit;

        $curl = curl_init();
        curl_setopt_array($curl, $curl_opts);
    
        $response = curl_exec($curl);
        // echo '<pre>'; var_dump($response); exit;
        if (curl_errno($curl)) {
            $error_msg = curl_error($curl);
            throw new \Exception($error_msg);
        }

        curl_close($curl);

        return $response;
    }

}