<?php


namespace Aoxiang\WebmanSunctum;


use Aoxiang\WebmanSunctum\Exception\AuthException;
use Aoxiang\WebmanSunctum\Exception\SunctumException;
use Aoxiang\WebmanSunctum\Exception\TokenException;
use Aoxiang\WebmanSunctum\Model\PersonalAccessToken;
use Illuminate\Support\Str;
use support\Cache;

class Auth
{
    protected $fromCache = true;
    /**
     * 自定义角色
     *
     * @var string
     */
    protected $guard = 'user';

    /** @var */
    protected $loginUser;
    /**
     * 配置信息
     *
     * @var array|mixed
     */
    protected $config = [];

    public function __construct()
    {
        $_config = config('plugin.aoxiang.webman-sunctum.app');
        if( empty($_config) ){
            throw new SunctumException('The configuration file is abnormal or does not exist');
        }
        $this->config = $_config;
    }

    /**
     * 设置当前角色
     *
     * @param  string  $name
     *
     * @return $this
     */
    public function guard(string $name) : Auth
    {
        $this->guard = $name;

        return $this;
    }

    public function user($cache = true)
    {
        $this->fromCache = $cache;
        $token           = $this->getTokenFormHeader();
        $tokenInfo       = $this->verifyToken($token);
        if( $tokenInfo === false ){
            throw new AuthException('Auth Fail');
        }

        if( $this->fromCache ){
            $user = Cache::get($this->getUserCacheKey($tokenInfo));
            if( is_null($user) ){
                return $this->userWithoutCache();
            }
        } else {
            return $this->getUserClass()->query()->where('id', $tokenInfo->tokenable_id)->first();
        }
    }

    /**
     * @return bool|\Illuminate\Database\Eloquent\Builder|\Illuminate\Database\Eloquent\Model|mixed|object
     */
    public function userWithoutCache()
    {
        return $this->user(false);
    }

    /**
     *
     * 只要 token 能通过校验，就算有登录，这里并不返回身份信息
     *
     * @return bool
     */
    public function isLogin()
    {
        $token = $this->getTokenFormHeader();
        $token = $this->verifyToken($token);
        if( $token === false ){
            return false;
        } else {
            return true;
        }
    }


    /**
     * @param $token
     *
     * @return bool|PersonalAccessToken|TokenException
     */
    public function verifyToken($token)
    {
        //todo redis

        if( strpos($token, "|") === false ){
            throw new TokenException('Token Error', 400);
        }
        list($id, $token) = explode("|", $token);
        $tokenInfo = null;
        if( $this->fromCache ){

            $tokenInfo = Cache::get($this->getTokenCacheKey($id));
        }

        if( empty($tokenInfo) || is_null($tokenInfo) ){
            //缓存中没有从 DB 中获取
            $tokenInfo = PersonalAccessToken::query()->where('id', $id)->first();
        }

        if( empty($tokenInfo) ){
            throw new TokenException('Token Not Exists');
        }

        if( hash_equals($tokenInfo->token, hash('sha256', $token)) ){
            return $tokenInfo;
        }

        return false;

    }

    /**
     * @param  array  $data
     *
     * @return $this|bool
     */
    public function attempt(array $data)
    {
        try {
            if( is_array($data) ){
                $user = $this->getUserClass();
                if( $user == null ){
                    throw new SunctumException('Model Not Exists', 400);
                }
                foreach ($data as $key => $val) {
                    if( $key !== 'password' ){
                        $user = $user->where($key, $val);
                    }
                }

                $user = $user->first();

                if( $user != null ){
                    if( isset($data['password']) ){
                        if( !password_verify($data['password'], $user->password) ){
                            throw new SunctumException('Password Error', 400);
                        }
                    }

                    return $this->login($user);
                }
                throw new SunctumException('Username Or Password Error', 400);
            }
            throw new SunctumException('Data should be array', 400);
        } catch (SunctumException $e) {
            throw new SunctumException($e->getMessage(), $e->getCode());
        }
    }


    /**
     * @param $user
     *
     * @return $this
     */
    public function login($user)
    {
        $this->loginUser = $user;

        return $this;
    }

    public function logout()
    {
        $token = $this->getTokenFormHeader();
        $token = $this->verifyToken($token);
        if( !empty($token) ){
            Cache::delete($this->getTokenCacheKey($token));
            Cache::delete($this->getUserCacheKey($token));
            PersonalAccessToken::query()->where('id', $token->id)->delete();
        }
    }

    /**
     * @return string
     */
    public function createToken()
    {

        //登录信息写入数据库
        /** @var PersonalAccessToken $token */
        $token = PersonalAccessToken::query()->create([
            'tokenable_type' => $this->config['guard'][$this->guard]['model'],
            'tokenable_id'   => $this->loginUser->id,
            'name'           => $this->guard,
            'token'          => hash('sha256', $plainTextToken = Str::random(40)),
        ]);
        //写入redis
        Cache::set($this->getUserCacheKey($token), $token);
        Cache::set($this->getUserCacheKey($token), $this->loginUser);

        return $token->getKey() . "|" . $plainTextToken;

    }


    /**
     * @return mixed|null
     */
    protected function getUserClass()
    {
        $guardConfig = $this->config['guard'][$this->guard]['model'];
        if( !empty($guardConfig) ){
            return new $guardConfig;
        }

        return null;
    }

    /**
     * 获取token信息
     *
     * @return bool|mixed|string|\Workerman\Protocols\Http\Session
     */
    protected function getTokenFormHeader()
    {
        $header = request()->header('Authorization', '');
        $token  = request()->input('_token');
        if( Str::startsWith($header, 'Bearer ') ){
            $token = Str::substr($header, 7);
        }

        if( !empty($token) && Str::startsWith($token, 'Bearer ') ){
            $token = Str::substr($token, 7);
        }

        $token = $token ?? session("token_{$this->guard}", null);

        if( empty($token) ){
            $token = null;
            $fail  = new SunctumException('尝试获取的Authorization信息不存在');
            $fail->setCode(401);
            throw $fail;
        }

        return $token;
    }


    /**
     * @param $key
     *
     * @return string
     */
    protected function cacheKey($key)
    {
        return config('app.name') . "_sunctum_" . $key;
    }

    /**
     * @param $token
     *
     * @return string
     */
    protected function getTokenCacheKey($token)
    {
        return $this->cacheKey('token_' . ($token instanceof PersonalAccessToken ? $token->id : $token));
    }

    /**
     * @param  PersonalAccessToken  $token
     *
     * @return string
     */
    protected function getUserCacheKey(PersonalAccessToken $token)
    {

        return $this->cacheKey('login_user_' . $token->tokenable_id);
    }
}
