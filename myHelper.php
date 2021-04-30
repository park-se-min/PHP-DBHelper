<?php
	class MyHelper
	{
		// DB
		private $dbHost = MY_DB_HOST;
		private $dbUser = MY_DB_USER;
		private $dbPassword = MY_DB_PASSWORD;
		private $dbDbname = MY_DB_DBNAME;
		private $dbPort = MY_DB_PORT;
		private $dbCharset = MY_DB_CHARSET;

		// pdo 객체
		private $db;

		// system
		private $error;
		private $debug;
		private $lock;

		// 배열 인덱스
		private $index = -1;
		private $index2 = -1;

		// SQL
		private $sql;
		private $sqlArray = array();
		private $sqlArrayValue = array();

		private $sqlTable = array();
		private $sqlParam = array();
		private $sqlValue = array();

		private $section;
		private $sectionArray = array();
		private $sectionColumn = array();
		private $sectionValue = array();

		// row
		private $sqlRow;
		private $sqlCount;

		// 최신 ID
		private $lastId;
		private $lastIdArray = array();

		// 페이징
		private $page = 1; // 현재페이지
		private $pageRow = 10; // 한 페이지에 보여줄 목록 개수
		private $pageStart; // 시작 열을 구함
		private $totalPage; // 전체 페이지
		private $listNumber; // 리스트 시작번호


		// --------------------------------------------------
		// 기본
		// --------------------------------------------------
		// 생성자
		public function __construct() {
			// DB 연결
			$this->sqlConnect();
		}

		// 소멸자
        public function __destruct() {
			// DB 연결해제
			unset($this->db);
		}



		// --------------------------------------------------
		// DB
		// --------------------------------------------------
		/* DB 연결 함수 */
		public function sqlConnect() {
			$this->sql = "mysql:host=". $this->dbHost ."; port=". $this->dbPort ."; dbname=". $this->dbDbname ."; charset=". $this->dbCharset .";";

			try {
				$this->db = new PDO($this->sql, $this->dbUser, $this->dbPassword);
				$this->db->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
				$this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
			} catch(PDOException $e) {
				echo __FILE__ ."<br>";
				echo $e->getMessage();
				exit;
			}
		}



		// --------------------------------------------------
		// SQL exec
		// --------------------------------------------------
		/* 실행 */
		public function execute() {
			// 디버그
			if (!empty($this->debug)) {
				$this->setPrint($this->sqlArray, $this->sqlArrayValue);
				$this->setDebug(null);
			}

			// SQL 실행, 인덱스 반환
			foreach ($this->sqlArray as $key=>$sql) {
				// 파라미터
				$arrayAalue = (!empty($this->sqlArrayValue[$key])) ? $this->sqlArrayValue[$key] : array();

				// 실행
				$this->db->prepare($sql)->execute($arrayAalue);

				// 최신 ID
				$this->lastIdArray[] = $this->lastId = $this->db->lastInsertId();
			}

			// 프로퍼티 재정의
			$this->resetPropertyLock();
		}

		/* 실행 */
		public function executeTran() {
			$this->beginTran();

			try {
				$this->execute();

				$this->commitTran();
			} catch(PDOException $e) {
				$this->rollbackTran();

				$this->setError($e);
			}
		}

		/* 리턴 */
		public function get() {
			try {
				// 디버그
				if (!empty($this->debug)) {
					$this->setPrint($this->sql, $this->sqlArrayValue);
					$this->setDebug(null);
				}

				// 파라미터
				$arrayAalue = (!empty($this->sqlArrayValue[$this->index])) ? $this->sqlArrayValue[$this->index] : array();

				// SQL 실행
				$stmt = $this->db->prepare($this->sql);

				// SQL 파라미터
				$stmt->execute($arrayAalue);

				// row 대입
//				$this->sqlRow = $stmt->fetchAll();
				$this->sqlRow = $stmt->fetchAll(PDO::FETCH_ASSOC);

				// 프로퍼티 재정의
				$this->resetPropertyLock();

				// 리턴
				return $this->sqlRow;
			} catch(PDOException $e) {
				// 에러
				$this->setError($e);

				// 프로퍼티 재정의
				$this->resetPropertyAll();
			}
		}

		/* 즉시 실행 */
		public function run($sql="") {
			// 디버그
			if (!empty($this->debug)) {
				$this->setPrint($sql);
				$this->setDebug(null);
			}

			// SQL 실행
			$stmt = $this->db->prepare($sql);

			// SQL 파라미터
			$stmt->execute();

			// 리턴
			try {
//				return $stmt->fetchAll();
				return $stmt->fetchAll(PDO::FETCH_ASSOC);
			} catch(PDOException $e) {
			}
		}

		/* 즉시 실행 */
		public function runTran($sql="") {
			$this->beginTran();

			try {
				$row = $this->run($sql);

				$this->commitTran();

				return $row;
			} catch(PDOException $e) {
				$this->rollbackTran();

				$this->setError($e);
			}
		}



		// --------------------------------------------------
		// SQL query
		// --------------------------------------------------
		/* SQL count */
		public function count() {
			// 조건
			$arrayWhere = $this->setSqlWhere()->setSqlDefault("where");

			// 그룹
			$arrayGroupBy = $this->setSqlGroupBy()->setSqlDefault("groupby");

			// having 조건
			$arrayHaving = $this->setSqlHaving()->setSqlDefault("having");

			// SQL
			$this->sql = "
				SELECT
					count(*) as count
				FROM
					". implode("", $this->getTable()) ."
				". $arrayWhere["start"] ." ". implode(" ", $arrayWhere["column"]) ."
				". $arrayGroupBy["start"] ." ". implode(" ", $arrayGroupBy["column"]) ."
				". $arrayHaving["start"] ." ". implode(" ", $arrayHaving["column"]) ."
			";

			// SQL value
			$this->sqlArrayValue[] = array_merge($arrayWhere["value"], $arrayHaving["value"]);

			// reset 예외
			// get 실행시 배열이 초기화 되기때문
			$this->setLock();

			// 실행
			$row = $this->get();

			// 총 개수
			$this->sqlCount = $row[0]["count"];

			return $this;
		}

		/* SQL count reset */
		public function countOnly() {
			$this->count();

			$this->resetPropertyAll();

			return $this;
		}

		/* SQL pageNation */
		public function pageNation() {
			// 전체 페이지 계산
			$this->totalPage  = ceil($this->sqlCount / $this->pageRow);

			// 리스트 시작번호
			$this->listNumber = $this->sqlCount - $this->pageRow * ($this->page-1);

			// limit
			$this->setPageLimit();

			return $this;
		}

		/* SQL select */
		public function select() {
			// 컬럼
			$arrayColumn = $this->setSqlColumn()->setSqlDefault("column");

			// 조건
			$arrayWhere = $this->setSqlWhere()->setSqlDefault("where");

			// 그룹
			$arrayGroupBy = $this->setSqlGroupBy()->setSqlDefault("groupby");

			// having 조건
			$arrayHaving = $this->setSqlHaving()->setSqlDefault("having");

			// 정렬
			$arrayOrderby = $this->setSqlOrderby()->setSqlDefault("orderby");

			// 제한
			$arrayLimit = $this->setSqlLimit()->setSqlDefault("limit");

			// SQL
			$this->sql = "
				SELECT
					". implode(", ", $arrayColumn["column"]) ."
				FROM
					". implode("", $this->getTable()) ."
				". $arrayWhere["start"] ." ". implode(" ", $arrayWhere["column"]) ."
				". $arrayGroupBy["start"] ." ". implode(" ", $arrayGroupBy["column"]) ."
				". $arrayHaving["start"] ." ". implode(" ", $arrayHaving["column"]) ."
				". $arrayOrderby["start"] ." ". implode(" ", $arrayOrderby["column"]) ."
				". $arrayLimit["start"] ." ". implode(" ", $arrayLimit["column"]) ."
			";

			// SQL value
			$this->sqlArrayValue[] = array_merge($arrayColumn["value"], $arrayWhere["value"], $arrayHaving["value"]);

			// 프로퍼티 재정의
			$this->resetPropertyOnly();

			return $this;
		}

		/* SQL insert */
		public function insert() {
			// SQL
			foreach ($this->sqlTable as $key=>$table) {
				$this->sqlArray[] = "
					INSERT INTO ". $table ." (
						". implode(", ", $this->sqlColumn[$key]) ."
					) VALUES (
						". implode(", ", $this->sqlParam[$key]) ."
					)
				";

				$this->sqlArrayValue[] = $this->sqlValue[$key];
			}

			// 프로퍼티 재정의
			$this->resetPropertyOnly();

			return $this;
		}

		/* SQL update */
		public function update() {
			// 컬럼 재조합
			$this->setUpdateColumn();

			// 조건
			$this->setSqlWhere();

			// SQL
			foreach ($this->sqlTable as $key=>$table) {
				$arrayWhere = $this->setSqlDefault("where", $key);

				$this->sqlArray[$key] = "
					UPDATE ". $table ." SET
						". implode(", ", $this->sqlColumn[$key]) ."
					". $arrayWhere["start"] ." ". implode(" ", $arrayWhere["column"]) ."
				";

				$this->sqlArrayValue[] = array_merge($this->sqlValue[$key], $arrayWhere["value"]);
			}

			// 프로퍼티 재정의
			$this->resetPropertyOnly();

			return $this;
		}

		/* SQL delete */
		public function delete() {
			// 조건
			$this->setSqlWhere();

			// SQL
			foreach ($this->sqlTable as $key=>$table) {
				$arrayWhere = $this->setSqlDefault("where", $key);

				$this->sqlArray[$key] = "
					DELETE FROM ". $table ."
					". $arrayWhere["start"] ." ". implode(" ", $arrayWhere["column"]) ."
				";

				$this->sqlArrayValue[] = $arrayWhere["value"];
			}

			// 프로퍼티 재정의
			$this->resetPropertyOnly();

			return $this;
		}



		// --------------------------------------------------
		// 그 외 함수
		// --------------------------------------------------
		/* 모든 프로퍼티 재정의 */
		// SQL 실행 후 프로퍼티 재정의
		// 재정의 안하면 계속 쌓임
		public function resetPropertyAll() {
			$this->resetPropertySqlOnly();

			$this->resetPropertyOnly();

			$this->setDebug(null);

			$this->setLock(null);
		}

		/* 프로퍼티 재정의 */
		// SQL 추가 후 재정의
		// insert 후 update 이런식으로 한번에 처리 가능하도록
		public function resetPropertyOnly() {
			$this->sqlTable = array();
			$this->sqlParam = array();
			$this->sqlValue = array();

			$this->sectionArray = array();
			$this->sectionColumn = array();
			$this->sectionValue = array();
		}

		/* SQL 프로퍼티 재정의 */
		// SQL 실행 후 재정의
		public function resetPropertySqlOnly() {
			$this->sqlArray = array();
			$this->sqlArrayValue = array();

			$this->section = null;

			$this->index = -1;
			$this->index2 = -1;
		}

		/* 프로퍼티 재정의 */
		public function resetPropertyLock() {
			// lock 없다면
			if (empty($this->lock)) {
				$this->resetPropertyAll();
			}
			// lock 있다면
			if (!empty($this->lock)) {
				$this->beforeSqlArrayValue();

				$this->setLock(null);
			}
		}

		/* 마지막에 실행한 value 초기화 */
		public function beforeSqlArrayValue() {
			array_pop($this->sqlArrayValue);

			return $this;
		}

		/* 트랜젝션 */
		public function beginTran() {
			$this->db->beginTransaction();
		}
		public function commitTran() {
			$this->db->commit();
		}
		public function rollbackTran() {
			$this->db->rollback();
		}

		/* update 컬럼 재조합 */
		public function setUpdateColumn() {
			foreach ($this->sqlTable as $key=>$table) {
				foreach ($this->sqlColumn[$key] as $key2=>$value) {
					$this->sqlColumn[$key][$key2] = $this->sqlColumn[$key][$key2] ."=?";
				}
			}
		}

		/* 에러 */
		public function setError($err="") {
			$this->error = $err;
		}
		public function getError() {
			return $this->error;
		}

		/* 디버그 */
		public function setPrint($sql="", $sqlValue="") {
			print_r("<pre>");
			print_r(str_repeat("-", 200) ."<br>");
			print_r($sql);
			print_r(str_repeat("-", 200) ."<br>");
			print_r($sqlValue);
			print_r(str_repeat("-", 200) . str_repeat("<br>", 10));
			print_r("</pre>");
		}
		public function setDebug($str="y") {
			$this->debug = $str;
		}

		/* 초기화 예외 */
		public function setLock($str="y") {
			$this->lock = $str;
		}

		/* 테이블 */
		public function setTable($table="") {
			$this->index++;
			$this->index2 = -1; // 2차 인덱스 초기화
			$this->sqlTable[$this->index] = $table;

			return $this;
		}
		public function getTable() {
			return $this->sqlTable;
		}

		/* 파라미터 */
		public function setParam($arr=array()) {
			// DB not null 때문에 빈값을 더해줌
			foreach ($arr as $key=>$value) {
				$arr[$key] = $value ."";
			}

			$this->sqlParam[$this->index] = array_fill(0, count($arr), "?");
			$this->sqlColumn[$this->index] = array_keys($arr);
			$this->sqlValue[$this->index] = array_values($arr);

			return $this;
		}

		/* 섹션 */
		// column, where, order by등등 통합함수
		public function setSection($column, $value="", $operator="and") {
			$this->index2++;

			$this->sectionArray[$this->section][$this->index][$this->index2]["column"] = $column;
			$this->sectionArray[$this->section][$this->index][$this->index2]["value"] = $value;
			$this->sectionArray[$this->section][$this->index][$this->index2]["operator"] = $operator;
		}

		/* 섹션 */
		// SQL 예외처리
		public function setSqlDefault($section, $index=0) {
			// index 적용
			$this->index = $index;

			// 컬럼
			if ($section == "column") { $arrayDefault = array("", array("*"), array()); }

			// 조건
			if ($section == "where") { $arrayDefault = array(" WHERE 1=1 ", array(), array()); }

			// 그룹
			if ($section == "groupby") { $arrayDefault = array(" GROUP BY ", array(), array()); }

			// having 조건
			if ($section == "having") { $arrayDefault = array(" HAVING 1=1 ", array(), array()); }

			// 정렬
			if ($section == "orderby") { $arrayDefault = array(" ORDER BY ", array(), array()); }

			// 제한
			if ($section == "limit") { $arrayDefault = array(" LIMIT ", array(), array()); }

			// 예외처리
			$start = (!empty($this->sectionColumn[$section][$this->index])) ? $arrayDefault[0] : "";
			$column = (!empty($this->sectionColumn[$section][$this->index])) ? $this->sectionColumn[$section][$this->index] : $arrayDefault[1];
			$value = (!empty($this->sectionValue[$section][$this->index])) ? $this->sectionValue[$section][$this->index] : $arrayDefault[2];

			// 대입
			$returnArray["start"] = $start;
			$returnArray["column"] = $column;
			$returnArray["value"] = $value;

			return $returnArray;
		}

		/* 섹션 재조합 */
		public function setSqlSection() {
			$sqlSection = $this->sectionArray;

			foreach ($this->sqlTable as $key=>$table) {
				if (!empty($sqlSection[$this->section][$key])) {

					$arrayColumn = array();
					$arrayAalue = array();

					// 배열에 담긴 where 조건
					foreach ($sqlSection[$this->section][$key] as $key2=>$value) {
						if (in_array($this->section, array("column", "groupby", "orderby", "limit"))) {
							$arrayColumn[] = $sqlSection[$this->section][$key][$key2]["column"];
						}

						if (in_array($this->section, array("where", "having"))) {
							$arrayColumn[] = $sqlSection[$this->section][$key][$key2]["operator"] ." ". $sqlSection[$this->section][$key][$key2]["column"];
						}

						// value 값이 배열인지 문자열인지 판별
						if (is_array($sqlSection[$this->section][$key][$key2]["value"])) {
							foreach ($sqlSection[$this->section][$key][$key2]["value"] as $value2) {
								$arrayAalue[] = $value2;
							}
						} else {
							$arrayAalue[] = $sqlSection[$this->section][$key][$key2]["value"];
						}
					}

					// 담기
					$this->sectionColumn[$this->section][$key] = $arrayColumn;
					$this->sectionValue[$this->section][$key] = $arrayAalue;
				}
			}
		}

		/* row */
		public function getRow() {
			return $this->sqlRow;
		}
		public function getCount() {
			return $this->sqlCount;
		}

		/* 리턴 인덱스 */
		public function getLastId() {
			return $this->lastId;
		}
		public function getLastIdArray() {
			return $this->lastIdArray;
		}
		public function resetLastIdArray() {
			$this->lastIdArray = array();
		}

		/* 컬럼 */
		public function setColumn($column, $value=array()) {
			$this->section = "column";

			$this->setSection($column, $value);

			return $this;
		}

		/* 컬럼 재조합 */
		public function setSqlColumn() {
			$this->section = "column";

			$this->setSqlSection();

			return $this;
		}

		/* 조건 */
		public function setWhere($column, $value="", $operator="and") {
			$this->section = "where";

			$this->setSection($column, $value, $operator);

			return $this;
		}

		/* 조건 재조합 */
		public function setSqlWhere() {
			$this->section = "where";

			$this->setSqlSection();

			return $this;
		}

		/* 그룹 */
		public function setGroupBy($column, $value="") {
			$this->section = "groupby";

			$this->setSection($column, $value);

			return $this;
		}

		/* 그룹 재조합 */
		public function setSqlGroupBy() {
			$this->section = "groupby";

			$this->setSqlSection();

			return $this;
		}

		/* having 조건 */
		public function setHaving($column, $value="", $operator="and") {
			$this->section = "having";

			$this->setSection($column, $value, $operator);

			return $this;
		}

		/* having 조건 재조합 */
		public function setSqlHaving() {
			$this->section = "having";

			$this->setSqlSection();

			return $this;
		}

		/* 정렬 */
		public function setOrderBy($column, $value="") {
			$this->section = "orderby";

			$this->setSection($column, $value);

			return $this;
		}

		/* 정렬 재조합 */
		public function setSqlOrderby() {
			$this->section = "orderby";

			$this->setSqlSection();

			return $this;
		}

		/* 제한 */
		public function setLimit($column, $value="") {
			$this->section = "limit";

			$this->setSection($column, $value);

			return $this;
		}

		/* 제한 재조합 */
		public function setSqlLimit() {
			$this->section = "limit";

			$this->setSqlSection();

			return $this;
		}

		/* 페이징 제한 */
		public function setPageLimit() {
			$this->setLimit($this->pageStart .", ". $this->pageRow);

			return $this;
		}

		/* 페이징 */
		public function setPage($page=1) {
			$this->page = $page;

			$this->setPageStart($this->pageRow);

			return $this;
		}

		/* 한 페이지에 보여줄 목록 개수 */
		public function setPageRow($pageRow=10) {
			$this->pageRow = $pageRow;

			$this->setPageStart($this->pageRow);

			return $this;
		}

		/* 시작 열을 구함 */
		public function setPageStart($pageRow=10) {
			$this->pageStart = ($this->page-1) * $this->pageRow;

			return $this;
		}

		/* 리스트 번호 */
		public function getListNumber() {
			return $this->listNumber;
		}
	}
?>
