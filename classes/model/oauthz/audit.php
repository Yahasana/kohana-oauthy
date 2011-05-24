<?php defined('SYSPATH') or die('No direct script access.');
/**
 * Handle OAuth data storage
 *
 * @author      sumh <oalite@gmail.com>
 * @package     Oauthz
 * @copyright   (c) 2010 OALite
 * @license     ISC License (ISCL)
 * @link        http://oalite.com
 * @see         Oauthz_Model
 * *
 */
class Model_Oauthz_Audit extends Oauthz_Model {

    public function get($access_token)
    {
        return ctype_digit($access_token)
            ? DB::select('access_token','created','nonce','secret_type')
                ->from('t_oauth_audits')
                ->where('access_token', '=', $access_token)
                ->execute($this->_db)
                ->current()
            : NULL;
    }

    /**
     * Insert audit
     *
     * @access	public
     * @param	array	$params
     * @return	mix     array(insert_id, affect_rows) or validate object
     */
    public function append(array $params)
    {
        $valid = Validate::factory($params);

        $rules = array_intersect_key(array (
            'nonce'         => array ('max_length'  => array (64)),
            'secret_type'   => array ('max_length'  => array (32)),
        ), $params);

        foreach($rules as $field => $rule)
            foreach($rule as $r => $p)
                $valid->rule($field, $r, $p);

        if($valid->check())
        {
            $valid = $valid->as_array();

            foreach($valid as $key => $val)
            {
                if($val === '') $valid[$key] = NULL;
            }

            $valid['created'] = $_SERVER['REQUEST_TIME'];
            return DB::insert('t_oauth_audits', array_keys($valid))
                ->values(array_values($valid))
                ->execute($this->_db);
        }
        else
        {
            // Validation failed, collect the errors
            return $valid;
        }
    }

    /**
     * Update audit
     *
     * @access	public
     * @param	int	    $access_token
     * @param	array	$params
     * @return	mix     update rows affect or validate object
     */
    public function update($access_token, array $params)
    {
        $valid = Validate::factory($params);

        $rules = array_intersect_key(array (
            'created'       => array ('not_empty'   => NULL, 'range' => array (0,2147483647)),
            'nonce'         => array ('max_length'  => array (64)),
            'secret_type'   => array ('max_length'  => array (32)),
        ), $params);

        foreach($rules as $field => $rule)
            foreach($rule as $r => $p)
                $valid->rule($field, $r, $p);

        if($valid->check())
        {
            $valid = $valid->as_array();

            foreach($valid as $key => $val)
            {
                if($val === '') $valid[$key] = NULL;
            }

            return DB::update('t_oauth_audits')
                ->set($valid)
                ->where('access_token', '=', $access_token)
                ->execute($this->_db);
        }
        else
        {
            // Validation failed, collect the errors
            return $valid;
        }
    }

    public function delete($access_token)
    {
        return ctype_digit($access_token)
            ? DB::delete('t_oauth_audits')
                ->where('access_token', '=', $access_token)
                ->execute($this->_db)
            : NULL;
    }

    /**
     * List audits
     *
     * @access	public
     * @param	array	    $params
     * @param	Pagination	$pagination	default [ NULL ] passed by reference
     * @param	boolean	    $calc_total	default [ TRUE ] is needed to caculate the total records for pagination
     * @return	array       array('audits' => data, 'orderby' => $params['orderby'], 'pagination' => $pagination)
     */
    public function lists(array $params, $pagination = NULL, $calc_total = TRUE)
    {
        $pagination instanceOf Pagination OR $pagination = new Pagination;

        $sql = 'FROM `t_oauth_audits` ';

        // Customize where from params
        //$sql .= 'WHERE ... '

        // caculte the total rows
        if($calc_total === TRUE)
        {
            $pagination->total_items = $this->_db->query(
                Database::SELECT, 'SELECT COUNT(`access_token`) num_rows '.$sql, FALSE
            )->get('num_rows');

            $data['pagination'] = $pagination;

            if($pagination->total_items === 0)
            {
                $data['audits'] = array();
                isset($params['orderby']) AND $data['orderby'] = $params['orderby'];
                return $data;
            }
        }

        // Customize order by from params
        if(isset($params['orderby']))
        {
            switch($params['orderby'])
            {
                case 'created':
                    $sql .= ' ORDER BY created DESC';
                    break;
                default:
                    $params['orderby'] = 'created';
                    $sql .= ' ORDER BY created DESC';
                    break;
            }
            $data['orderby'] = $params['orderby'];
        }

        $sql .= " LIMIT {$pagination->offset}, {$pagination->items_per_page}";

        $data['audits'] = $this->_db->query(Database::SELECT, 'SELECT * '.$sql, FALSE);

        return $data;
    }

} // END Model_Audit
