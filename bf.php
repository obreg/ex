<?php  
/**
 * 
 * ************************************************* 
 * Created on :2013-18-20   下午02:47:41
 * Encoding   :UTF-8
 * Description: 判断一个数是否在一个集合中
 * @Author @IGOOGLE
 * @WAP_WEIBO (C)1996-2099 SINA Inc. 
 * ************************************************
 */

class BloomFilter 
{  
	/** 
     * 允许的出错率 px：0.01 => 1%
     * @var double 
     */  
    private $f;  

    /** 
     * hashmap的大小bit size
     * @var int 
     */  
    private $m;  
  
    /** 
     * 哈希的次数
     * @var int 
     */  
    private $k;  
  
    /** 
     * 被验证的集合的数量
     * @var int 
     */  
    private $n;  
  
    /** 
     * The bitset name
     * @var string 
     */  
    private $field;  
  
	/**
	 * The Hash Key
	 * @var string
	 **/
	private $hash_key;

	/**
	 * The Item To Set Or Get
	 *
	 **/
	private $skey

	private $redis;

    public function __construct($field, $f = 0.01, $n = '1000000') 
	{  
		if (!$field)
		{
			return 'NOT FOUND FIELD';
			die();
		}

		$this->field = $field; 
        $this->f = $f; 
		$this->n = $n;

		$this->redis = new Redis();
		$this->redis->connect('172.16.11.212', 6379, 5);
    } 

	/** 
     * Adds a new item to the filter 
     */  
    public function set($key) 
	{  
		$this->skey = $key;
		$this->getHashKey();	
		return $this->setHashMap();
    } 

    /** 
     * Hashes the argument to a number of positions in the bit set and returns the positions 
     * 
     */  
    protected function get($key) 
	{  
		$this->skey = $key;
		$this->getHashKey();	
		return $this->getHashMap();
    } 

	/**
	 *	获取HASH_kEY => 针对大数据进行hash分拆，假如1亿的数据，100w为一组
	 *
	 */
	private function getHashKey()
	{
		$this->hash_key = $this->field . round(hexdec(substr(MD5($this->skey), 1, 16) % 100);
	}

    /** 
     * 计算最优的hash函数个数：当hash函数个数k=(ln2)*(m/n)时错误率最小 
     * 
     * @param int $m bit数组的宽度（bit数） 
     * @param int $n 加入布隆过滤器的key的数量 
     * @return int 
     */  
    public static function getHashCount() 
	{  
        $this->k = ceil(($this->m / $this->n) * log(2));  
    }   
  
	/**
	 * 计算出所需的bit数组大小, 用于进一步计算最优k值
	 * 
	 *@param int $n Number of elements in the filter 
	 *@param double $f False Positive的比率(0.01)
	 */
	public static function getBitSize($n = 1000000, $f = 0.01)
	{
		$this->m = 0.818 * $n * log(1/$f, 2);
	}

    /** 
     * False Positive的比率：f = (1 – e-kn/m)k    
     * Returns the probability for a false positive to occur, given the current number of items in the filter 
     * 
     * @return double 
     */  
    public function getFalsePositiveProbability() 
	{  
        $exp = (-1 * $this->k * $this->n) / $this->m;  
        return pow(1 - exp($exp),  $this->k);  
    }   
  
	/**
	 * 设置hashmap的值
	 * 
	 */
    public function setHashMap() 
	{  
		foreach ($i = 1; $i <= $this->k; $i++)
		{
			$this->skey = substr(hexdec(MD5($this->skey)), 1, 16) * $i;
			$redis_key = round($this->skey % $this->m);
			$this->redis->setbit($this->hash_key, $redis_key, 1);
		}
        return true;  
    }   

	/**
	 * 获取hashmap的值
	 * 
	 */
    public function getHashMap() 
	{  
		foreach ($i = 0; $i < $this->k; $i++)
		{
			$this->skey = substr(hexdec(MD5($this->skey)), 1, 16) * $i;
			$redis_key = round($this->skey % $this->m);
			$bit_value = $this->redis->getbit($this->hash_key, $redis_key);

			if(!$bit_value)
			{
				return false;
			}
		}
        return true;  
    } 
}   
