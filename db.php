<?php
class Db {
    
    public $debug = true;
    
    //these will hold the database credentials
    private $hostname;
    private $database;
    private $username;
    private $password;
    
    //this will hold the current table being queried
    private $table = '';
    
    //this will hold the query to be executed
    private $query = '';
    
    //where queries will be captured in this array
    private $where = array();
    
    //all binded parameter will be captured in this array
    private $bind = array();
    
    /*
     * constructor
     *
     * initialises the database connection
     *
     * @param string $hostname hostname eg: localhost
     * @param string $database name of database
     * @param string $username name of database user
     * @param string $password password for database user
     */
    public function __construct( $hostname='', $database='', $username='', $password='' ) {        
        //set the database credentials as class properties
        $this->hostname = $hostname;
        $this->database = $database;
        $this->username = $username;
        $this->password = $password;
        
        try {            
            //we are using PDO class
            $this->connection = new PDO('mysql:host='.$this->hostname.';dbname='.$this->database.';charset=utf8', $this->username, $this->password);
            
            //set error and exception mode on
            $this->connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        }
        catch(PDOException $e) {            
            //if debug mode on then show the error
            if( $this->debug )
            echo $e->getMessage();
            
            die();
        }
    }
    
    /*
     * reset all the query properties
     * when creating a query for a table the properties if set by any previous query needs to be reset
     * 
     */
    public function reset() {
        $this->table = '';
        $this->join = '';
        $this->select = '';
        $this->where = array();
        $this->bind = array();
    }
    
    /*
     * @param string $table_name
     * @return current object
     */
    public function table($table_name) {
        $this->reset();
        $this->table = $table_name;
        return $this;
    }
    
    /*
     * get the current table to be queried
     *
     * if table property is not set then the lowercase name of the class is
     * assumed as the table name
     *
     * @return string
     */
    public function get_table() {
        return !( empty($this->table) ) ? $this->table : strtolower(get_class($this));
    }
    
    /*
     * sets the where condition of the query
     * 
     * @param string $column_name
     * @param string $condition
     * @param int/string $value
     *
     * @return current object
     */
    public function where($column_name, $condition, $value) {
        $this->where[] = $column_name.' '.$condition.' ?';
        $this->bind[] = $value;
        return $this;
    }
    
    public function orWhere($column_name, $condition, $value) {
        $this->where[] = ' OR '.$column_name.' '.$condition.' ?';
        $this->bind[] = $value;
        return $this;
    }
    
    /*
     * sets the where in condition of the query
     *
     * @param string $column_name
     * @param array $value_array numeric array expected
     *
     * @return current object
     */
    public function whereIn($column_name, $value_array) {
        $escaped_chars = array();
        foreach( $value_array as $value ) {
            $this->bind[] = $value;
            $escaped_chars[] = '?';
        }
        $this->where[] = $column_name.' IN ('.implode(',',$escaped_chars).')';
        return $this;
    }
    
    /*
     * sets where between condition of the query
     *
     * @param string $column_name
     * @param array $value_array numeric array with two elements
     *
     * @return current object
     */
    public function whereBetween($column_name, $value_array) {
        $escaped_chars = array();
        foreach( $value_array as $value ) {
            $this->bind[] = $value;
        }
        $this->where[] = $column_name.' BETWEEN ? AND ?';        
        return $this;
    }
    
    /*
     * Execute the query after binding parameters
     * 
     */
    public function query($query='',$bind_array=array()) {
        try {
            $this->query = $query;
            $this->stmt = $this->connection->prepare($query);
            $this->stmt->execute($bind_array);
            return true;
            /*
            if($return_result) {
                //if select empty or insert or update or delete then columncount returns 0
                if( $this->stmt->columnCount() == 0 ) {
                    return $this->stmt->rowCount();                    
                }
                else {
                    return $this->stmt->fetchAll(PDO::FETCH_OBJ);
                }
            }
            else
            return true;
            */
        }
        catch( PDOException $e ) {
            if( $this->debug )
            echo $e->getMessage();
            return false;
        }
        /*
        if($return_result) {
            try {
                //if select empty or insert or update or delete then columncount returns 0
                if( $this->stmt->columnCount() == 0 ) { //column count 0 means this stmt modifies rows
                    return $this->stmt->rowCount();                    
                }
                else {
                    return $this->stmt->fetchAll(PDO::FETCH_OBJ);
                }
            }
            catch( PDOException $e ) {
                if( $this->debug )
                show_error($e->getMessage()." for below query<br/>$query");
                return false;            
            }
            return $result_set;
        }
        else
        return true;
        */
    }
    
    public function sel($raw_query='',$bind_params) {
        if( $this->query($raw_query,$bind_params) )
            return $this->fetch_results();
        else
            return false;
    }
    
    /*
     * fetch results for last select statement
     *
     * @return array
     */
    private function fetch_results() {
        return $this->stmt->fetchAll(PDO::FETCH_OBJ);
    }
    
    /*
     * fetch row count for last delete, insert or update statement
     *
     * @return int
     */
    private function fetch_row_count() {
        return $this->stmt->rowCount();
    }
    
    /*
     * build and return where condition
     *
     * @return string or null
     */
    private function get_where() {
        if( $this->where ) {
            $return_string = '';
            $count = 0;
            foreach( $this->where as $where ) {
                if($count != 0) {
                    if(strpos($where, " OR ") === 0) {
                        //$return_string .= " OR ";
                    }
                    else {
                        $return_string .= " AND ";
                    }    
                }                
                $return_string .= $where;
                $count++;
            }
            return " WHERE ".$return_string;    
            //return " WHERE ".implode(' AND ',$this->where);    
        }        
        else
        return NULL;
    }
    
    /*
     * calls get method with limit 1 and offset 0
     * 
     * @return mixed empty array if no result found, object if result found and false if query fails
     */
    public function first() {
        $result = $this->get(1,0);
        if( is_array($result) ) {
            if(isset($result[0])) {
                return $result[0];
            }
            else
                return [];
        }
        else
            return false;
    }
    
    /*
     * builds and executes the select statement and returns the result
     *
     * @return array if success and false if any error occurs
     */
    public function get($limit=null,$offset=0) {
        $result = null;
        $query = "SELECT ".$this->get_select()." FROM ".$this->get_table(). $this->get_joins(). $this->get_where();
        
        if( $limit ) $query .= " LIMIT ".$offset.",".$limit;
        
        if( $this->query($query,$this->bind) )
            return $this->fetch_results();
        else
            return false;
    }
    
    /*
     * returns count of a select statement
     * This doesn't use row count but instead uses SELECT COUNT(0)
     *
     * @return int if success else boolean false if any error occurs
     */
    public function count() {
        $result = null;
        $query = "SELECT COUNT(0) AS total FROM ".$this->get_table(). $this->get_joins(). $this->get_where();
        if( $this->query($query,$this->bind) ) {
            $results = $this->fetch_results();
            return $results[0]->total;
        }
        else
            return false;
    }
    
    /*
     * TO BE IMPLEMENTED
     */
    private function get_joins() {
        if( isset($this->join) && !empty($this->join) ) {
            return " ".$this->join." ";
        }
        else
        return '';
    }
    
    /*
     * delete row/rows
     *
     * @return no of rows deleted
     */
    public function delete() {
        $query = "DELETE FROM ".$this->get_table(). $this->get_where();
        $this->query($query,$this->bind);
        return $this->fetch_row_count();
    }
    
    /*
     * insert
     *
     * @return last insert id
     */
    public function insert($insert_array=array()) {
        $insert_columns = $insert_values = $bind_values = array();
        foreach( $insert_array as $column=>$value ) {
            $insert_columns[] = $column;
            $insert_values[] = $value;
            $bind_values[] = '?';
        }
        $query = "INSERT INTO ".$this->get_table()." (".implode(',',$insert_columns).") VALUES (".implode(',',$bind_values).")";
        $this->query($query,$insert_values);
        return $this->connection->lastInsertId();
    }
    
    /*
     * get the last query executed
     *
     * @return string
     */
    public function get_last_query() {
        return $this->query;
    }
    
    /*
     * get the select columns query
     */
    private function get_select() {
        if( isset($this->select) && !empty($this->select) ) {
            return $this->select;
        }
        else
            return '*';
    }
    
    /*
     * select columns
     */
    public function select($columns) {
        $this->select = $columns;
        return $this;
    }
    
    public function update($update_data=array()) {
        $update_columns = $update_values = $bind_values = array();
        foreach( $update_data as $column=>$value ) {
            $update_columns[] = $column.' = ?';
            $update_values[] = $value;         
        }
        $query = "UPDATE ".$this->get_table()." SET ".implode(', ',$update_columns).$this->get_where();
        $bind_params = array_merge($update_values,$this->bind);
        $this->query($query,$bind_params);
        return $this->fetch_row_count();
    }
    
    /*
     * TO BE IMPLEMENTED
     */
    public function leftJoin($table,$column1,$on,$column2) {
        $this->join = "LEFT JOIN ".$table." ON ".$column1." ".$on." ".$column2;
        return $this;
    }
    
    /*
     * use of timestamps
     * TO BE IMPLEMENTED
     */
    public function _save($data) {
        $insert_data = array();
        $insert_data['created_at'] = $insert_data['updated_at'] = date('Y-m-d H:i:s');
        foreach( $this->fillable as $db_column ) {
            if(isset($data[$db_column])) {
                $insert_data[$db_column] = $data[$db_column];
            }
        }
        return $this->_table(strtolower(get_class($this)))->insert($insert_data);
    }
    
    public function all() {
        return $this->table($this->get_table())->get();
    }
}