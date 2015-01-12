<?php
/* Klasa do obs3ugi po3ącze&#65533;bazą MySQL
 * Wi&#39603;zo¶&#18406;unkcji robi, a przynajmniej mo&#191;e robi&#19104;w tle r&#56250;&#57189; fajne rzeczy,
 * wi&#39008;u&#191;ywając klasy nie nale&#191;y bezpo¶rednio korzysta&#18426;e zmiennych, ale 
 * wszystko robi&#18416;oprzez funkcje.
 *
 *
 * made by Szymon Guz
 * version: 0.9.1
 */
/*

Konfiguracja:

bool     $DEBUG_ON                    <- czy w3ączy&#18412;ogowanie zapyta&#65533;b3&#39283;w

bool     $DEBUG_WRITE_QUERY           <- czy w3ączy&#18412;ogowanie zapyta&#65533;
string   $DEBUG_WRITE_QUERY_TO_FILE   <- ¶cie&#191;ka do pliku z logami zapyta&#65533;
bool     $DEBUG_WRITE_ERRORS          <- czy w3ączy&#18412;ogowanie b3&#39283;w

string   $DEBUG_WRITE_ERRORS_TO_FILE  <- ¶cie&#191;ka do pliku z logami b3&#39283;w

string   $DEBUG_END_LINE              <- znak ko&#55369;&#57312;linii dla plik&#55957;&#57338; logami


Funkcje:
MyDB($host,$login,$password,$database) <- zwyk3y konstruktor, kt&#55943;&#56800;przyjmuje jako parametry wszystko co trzeba do po3ączenia z bazą

bool Connect() <- 3ączy si&#34810; bazą u&#191;ywając zwyk3ego po3ączenia
           ZWRACANA WARTO¦ĺ
            - true   - po3ączenie powiod3o si&#33482;            - false  - po3ączenie nie powiod3o si&#33482;
bool PConnect() <- 3ączy si&#34810; bazą u&#191;ywając sta3ego po3ączenia
           ZWRACANA WARTO¦ĺ
            - true   - po3ączenie powiod3o si&#33482;            - false  - po3ączenie nie powiod3o si&#33482;
bool Query($query) <- wysy3a zapytanie do bazy
           ZWRACANA WARTO¦ĺ
            - true   - zapytanie powiod3o si&#33482;            - false  - zapytanie nie powiod3o si&#33482;
Funkcje zaczynające si&#34799;d Fetch mo&#191;na u&#191;ywa&#18404;ok3adnie tak samo jak funkcje mysql_fetch_**() w p&#40172;i while.

mixed FetchAssoc() <- funkcja wywo3uje 
           ZWRACANA WARTO¦ĺ
            - wiersz wynik&#55957;&#57338;apytania (jako tablica asocjacyjna) - je¶li si&#34807;szystko uda3o
            - false  - nie ma ju&#191; wynik&#55957;&#57335; tablicy

mixed FetchNum() <- funkcja wywo3uje 
           ZWRACANA WARTO¦ĺ
            - wiersz wynik&#55957;&#57338;apytania (jako tablica indeksowana liczbami) - je¶li si&#34807;szystko uda3o
            - false  - nie ma ju&#191; wynik&#55957;&#57335; tablicy            

	  FetchLastInsertId() <-funkcja zwraca id ostatnio dodanego wiersza	
				
mixed FetchArray() <- funkcja wywo3uje 
           ZWRACANA WARTO¦ĺ
            - wiersz wynik&#55957;&#57338;apytania (jako obie tablice: asocjacyjna i indeksowana) - je¶li si&#34807;szystko uda3o
            - false  - nie ma ju&#191; wynik&#55957;&#57335; tablicy
            
mixed Result($row,$col) <- dzia3a dok3adnie jak funkcja mysql_result
            
int GetLastErrorNo() <- zwraca numer ostatniego b3&#39157;

int GetLastError() <- zwraca tekst ostatniego b3&#39157;

bool Free() <- czy¶ci tymczasowe tablice z wynikami zapytania
           ZWRACANA WARTO¦ĺ
            - true   - skasowanie danych powiod3o si&#33482;            - false  - skasowanie danych nie powiod3o si&#34701;

void Disconnect() <- roz3ącza si&#34810; bazą

int GetNumRows() <- zwraca ilo¶&#18423;ierszy wyniku

int GetNumFields() <- zwraca ilo¶&#18416;&#55913;&#57335; wierszu wyniku

int AffectedRows() <- zwraca ilo¶&#18416;rzetworzonych wierszy w związku z wykonywaniem zapytania           
            
*/

define('CONNECTION_ERROR','Error while connection to the database');

class MyDB {
    var $DEBUG_ON                    =true;
    var $DEBUG_WRITE_QUERY           =false;
    var $DEBUG_WRITE_QUERY_TO_FILE   ='logs/db_query.log';
    var $DEBUG_WRITE_ERRORS          =false;
    var $DEBUG_WRITE_ERRORS_TO_FILE  ='logs/db_error.log';
    var $DEBUG_END_LINE              ="\r\n";
    
    var $m_db;
    var $m_host;
    var $m_login;
    var $m_password;
    var $m_database;
    var $m_query;
    var $m_query_id;
    var $m_error_no;
    var $m_error;
	 var $m_connected;
    
    //tmp by denis 
    var $st;
    var $et;
    
	 
	 
    function getmicrotime() {
        list($usec, $sec) = explode(" ",microtime());
        return ((float)$usec + (float)$sec);
    }
    
    function WriteError() {
        $this->m_error_no=@mysql_errno($this->m_db);
        $this->m_error   =@mysql_error($this->m_db);
        if($this->DEBUG_ON && $this->DEBUG_WRITE_ERRORS && $this->m_error!='') {
            $Log =date("d-m-Y H:i:s").'\n\r ';
            $Log.=': Host:'         .$this->m_host;
            $Log.=': RemoteHost:'         .$_SERVER['REMOTE_ADDR'];
            $Log.=': Login:'        .$this->m_login;
            $Log.=': BaseName:'     .$this->m_database;
            $Log.=': Query:"'        .$this->m_query.'"';
            $Log.=': ErrorNumber:'  .$this->m_error_no;
            $Log.=': Error:"'        .$this->m_error.'"';
            $Log.=$this->DEBUG_END_LINE;
            $FILE=fopen($this->DEBUG_WRITE_ERRORS_TO_FILE,'a+');
            fwrite($FILE,$Log);
            fclose($FILE);
        }
    }
    
    function WriteQuery() {
        if($this->DEBUG_ON && $this->DEBUG_WRITE_QUERY) {
            $Log =$t.' '.date("d-m-Y H:i:s").'  ';
            $Log.=': Host:'         .$this->m_host;
            $Log.=': RemoteHost:'         .$_SERVER['REMOTE_ADDR'];
            $Log.=': Login:'        .$this->m_login;
            $Log.=': BaseName:'     .$this->m_database;
            $Log.=': Query:"'        .$this->m_query.'"';
            $Log.=$this->DEBUG_END_LINE;
            $FILE=fopen($this->DEBUG_WRITE_QUERY_TO_FILE,'a+');
            fwrite($FILE,$Log);
            fclose($FILE);
        }
    }
    
    function MyDB($host,$login,$password,$database) {
        $this->m_host=$host;
        $this->m_login=$login;
        $this->m_password=$password;
        $this->m_database=$database;
        $this->m_db='';
	$this->m_connected=false;	
    }
    
    function Connect() {
        $this->m_db=mysql_connect($this->m_host,$this->m_login,$this->m_password);
        if(!$this->m_db) {
            $this->m_db='';
				$this->m_connected=false;
            $this->m_error=CONNECTION_ERROR;
            return false;
        }
        @mysql_select_db($this->m_database,$this->m_db);
		  $this->m_connected=true;
		  return true;		
    }
    
    function PConnect() {
        $this->m_db=@mysql_pconnect($this->m_host,$this->m_login,$this->m_password);
        if(!$m_db) {
            $this->m_db='';
            $this->m_error=CONNECTION_ERROR;
            return false;
        }
        @mysql_select_db($this->m_db);
    }
    
    function Query($query) {       
        $this->m_query=$query;        
        $this->m_query_id=@mysql_query($query,$this->m_db);
        $this->WriteQuery();
        if(!$this->m_query_id) {
            $this->m_error_no=@mysql_errno($this->m_db);
            $this->m_error   =@mysql_error($this->m_db);
        }
        
        $this->WriteError();
        if(!$this->m_query_id) return false;
        return true;
    }
    
    function FetchAssoc() {
        $res=@mysql_fetch_array($this->m_query_id,MYSQL_ASSOC);
        if(!$res) {
            $this->m_error_no=@mysql_errno($this->m_db);
            $this->m_error   =@mysql_error($this->m_db);
        }
        $this->WriteError();
        return $res;
        //      return false;
    }
    
    function FetchNum() {
        $res=@mysql_fetch_array($this->m_query_id,MYSQL_NUM);
        if(!$res) {
            $this->m_error_no=@mysql_errno($this->m_db);
            $this->m_error   =@mysql_error($this->m_db);
        }
        $this->WriteError();
        if($res) return $res;
        return false;
    }
    
    function FetchArray() {
        $res=@mysql_fetch_array($this->m_query_id,MYSQL_BOTH);
        if(!$res) {
            $this->m_error_no=@mysql_errno($this->m_db);
            $this->m_error   =@mysql_error($this->m_db);
        }
        $this->WriteError();
        if($res) return $res;
        return false;
    }
	 function FetchLastInsertId() {
        return @mysql_insert_id($this->m_db);
    }
	 function FetchFieldNames(){
	     $num_fields=$this->GetNumFields();
	     if ($num_fields==0){return false;}
		  $res=array();	
	     for ($i=0;$i<$num_fields;$i++){
			                              $res[$i]=mysql_field_name($this->m_query_id, $i); 
	                                   }
		  return $res;											
	 }
	 function FetchFieldTypes(){
	     $num_fields=$this->GetNumFields();
	     if ($num_fields==0){return false;}
		  $res=array();	
	     for ($i=0;$i<$num_fields;$i++){
			                              $res[$i]=mysql_field_type($this->m_query_id, $i); 
	                                   }
		  return $res;											
	 }
    function Result($row,$col) {
        return @mysql_result($this->m_query_id,$row,$col);
    }
    
    function GetLastError() {
        return $this->m_error;
    }
    
    function GetLastErrorNo() {
        return $this->m_error_no;
    }
    
    function Free() {
        $res=@mysql_free_result($this->m_query_id);
        if(!$res) {
            $this->m_error_no=@mysql_errno($this->m_db);
            $this->m_error   =@mysql_error($this->m_db);
        }
        $this->WriteError();
        if($res) return true;
        return false;
    }
    
    function Disconnect() {
        @mysql_close($this->m_db);
		  $this->m_connected=false;	
    }
    
	 function CloneConnection() {
        $dbclone=new MyDB($this->m_host,$this->m_login,$this->m_password,$this->m_database);
		  if ($this->m_connected==true){$dbclone->Connect();}
		  return $dbclone;		
    }
	 
    function GetNumRows() {
        return @mysql_num_rows($this->m_query_id);
    }
    
    function GetNumFields() {
        return @mysql_num_fields($this->m_query_id);
    }
    
    function AffectedRows() {
        return @mysql_affected_rows($this->m_db);
    }
	 
	 
}

?>