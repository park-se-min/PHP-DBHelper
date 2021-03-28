# 🔥 PHP-DBHelper 🔥


## 💡 include

```php
<?php
	include_once $_SERVER['DOCUMENT_ROOT'] .'/classes/myHelper.php';

	$my = new MyHelper();
?>
```



## 💡 setup

```php
<?php
	/* 주의사항 */
	// run 제외하고 setTable 부터 꼭 해야됨

	// --------------------------------------------------
	/* 초기화 */
	// --------------------------------------------------
	$my->run("DROP TABLE memberTest");
	$my->run("DROP TABLE productTest");

	// --------------------------------------------------
	/* 테이블 */
	// --------------------------------------------------
	$my->run("
		CREATE TABLE IF NOT EXISTS memberTest (
			mbSeq int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
			mbId varchar(50) NOT NULL,
			mbName varchar(255) NOT NULL,
			mbRegDate datetime NOT NULL
		) ENGINE=InnoDB DEFAULT CHARSET=utf8;
	");

	$my->run("
		CREATE TABLE IF NOT EXISTS productTest (
			pdId int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
			mbId varchar(50) NOT NULL,
			pdTitle varchar(255) NOT NULL,
			pdContent text NOT NULL,
			pdRegDate datetime NOT NULL
		) ENGINE=InnoDB DEFAULT CHARSET=utf8;
	");
?>
```



## 💡 data

```php
<?php
	// --------------------------------------------------
	/* 파라미터 */
	// --------------------------------------------------
	$memberTestParam = array();
	$productTestParam = array();
	$rand = array();

	for ($i=0; $i<100; $i++) {
		$rand[$i] = rand(1000, 9999);

		// memberTest
		$param = array();
		$param["mbId"] = "아이디". $i ."_". $rand[$i];
		$param["mbName"] = "이름". $i ."_". $rand[$i];
		$param["mbRegDate"] = date("Y-m-d H:i:s");

		$memberTestParam[] = $param;

		// productTest
		$param = array();
		$param["mbId"] = "아이디". $i ."_". $rand[$i];
		$param["pdTitle"] = "제목". $i ."_". $rand[$i];
		$param["pdContent"] = "내용". $i ."_". $rand[$i];
		$param["pdRegDate"] = date("Y-m-d H:i:s");

		$productTestParam[] = $param;
	}
?>
```
---


## 💡 insert

```php
<?php
	// 방식1
	$my->setTable("memberTest");
	$my->setParam($memberTestParam[0]);

	$my->insert();
	$my->execute();


	// 방식2
	$my->setTable("memberTest")->setParam($memberTestParam[1]);

	$my->insert();
	$my->executeTran();


	// 방식3
	$my->setTable("memberTest")->setParam($memberTestParam[2]);
	$my->setTable("memberTest")->setParam($memberTestParam[3]);
	$my->setTable("memberTest")->setParam($memberTestParam[4]);
	$my->setTable("memberTest")->setParam($memberTestParam[5]);
	$my->setTable("memberTest")->setParam($memberTestParam[6]);
	$my->setTable("memberTest")->setParam($memberTestParam[7]);
	$my->setTable("memberTest")->setParam($memberTestParam[8]);
	$my->setTable("memberTest")->setParam($memberTestParam[9]);

	$my->insert();
	$my->executeTran();


	// 방식4
	for ($i=0; $i<10; $i++) {
		$my->setTable("productTest")->setParam($productTestParam[$i]);
	}

	// $my->setDebug(); // 쿼리로그 찍어보기
	$my->insert()->executeTran();


	// 방식5
	$my->setTable("memberTest")->setParam($memberTestParam[1]);

	$my->insert();
	$my->executeTran(); // 트랜젝션 하면서 실행


	// 방식6
	$my->beginTran();

	try {
		$my->setTable("memberTest");
		$my->delete();

		$my->setTable("memberTest")->setParam($memberTestParam[10]);
		$my->setTable("memberTest")->setParam($memberTestParam[11]);
		$my->setTable("memberTest")->setParam($memberTestParam[12]);
		$my->setTable("memberTest")->setParam($memberTestParam[13]);
		$my->setTable("memberTest")->setParam($productTestParam[1]); // err

		$my->insert();
		$my->execute(); // no tran


		$my->run("DELETE FROM memberTest");

		$row = $my->run("SELECT * FROM memberTest");

		$my->setPrint($row);

		$my->commitTran();
	} catch(PDOException $e) {
		$my->rollbackTran();

		// run 으로 이용할땐 상관없음
		// dbHelper 이용시 필수로 넣어줘야됨. (다음 코드가 실행되기때문.)
		$my->resetPropertyAll();
	}
?>
```
---


## 💡 insert > last id

```php
<?php
	// last id
	print_r($my->getLastId());
	print_r($my->getLastIdArray());
?>
```
---

## 💡 update
```php
<?php
	// 방식1
	// 1번데이터 5번데이터로 update
	$my->setTable("memberTest")->setParam($memberTestParam[4])->setWhere("mbSeq=?", "1")->update()->executeTran();


	// 방식2
	// where 조건이 많을때
	$my->setTable("memberTest")->setParam($memberTestParam[3]);
	$my->setWhere("mbSeq=?", "1");
	$my->setWhere("mbSeq=?", "10");
	$my->setWhere("mbId like ?", "%아이디%", "or");
	$my->setWhere("(mbSeq=? or mbSeq=? or mbSeq=? or mbSeq=?)", array("2", "3", "4", "5"), "or");

	$my->setTable("memberTest")->setParam($memberTestParam[4]);
	$my->setWhere("mbSeq=?", "1");

	$my->setTable("memberTest")->setParam($memberTestParam[5]);
	$my->setWhere("mbSeq=?", "1");
	$my->setWhere("mbSeq=?", "2", "or");

	$my->setTable("memberTest")->setParam($memberTestParam[2]);
	$my->setWhere("mbSeq=?", "6");

	// $my->setDebug(); // 쿼리로그 찍어보기
	$my->update()->executeTran();
?>
```
---


## 💡 delete

```php
<?php
	// 방식1
	$my->setTable("memberTest")->setWhere("mbSeq=?", "1")->delete()->executeTran();


	// 방식2
	$my->setTable("memberTest");
	$my->setWhere("mbSeq=?", "1");
	$my->setWhere("mbSeq=?", "10");
	$my->setWhere("(mbSeq=? or mbSeq=? or mbSeq=? or mbSeq=?)", array("2", "3", "4", "5"), "or");

	$my->setTable("memberTest");
	$my->setWhere("mbSeq=?", "10");

	// $my->setDebug(); // 쿼리로그 찍어보기
	$my->delete();
	$my->executeTran();
?>
```
---


## 💡 list

```php
<?php
	// 방식1
	$row = $my->setTable("memberTest")->select()->get();

	echo "<br>";
	for ($i=0; $i<count($row); $i++) {
		print_r($row[$i]); echo "<br>";
	}
	echo "<br>";


	// 방식2
	$my->setTable("memberTest");
	$my->setWhere("mbSeq=?", "5");
	$my->setWhere("mbSeq=?", "6", "or");
	$my->setWhere("mbSeq=?", "7", "or");
	$my->setWhere("mbSeq=?", "8", "or");
	$my->setOrderBy("mbSeq desc");

	// 총 개수
	$totalCount = $my->count()->getCount();

	// 페이징
	$my->setPage(1)->setPageRow(2)->pageNation();
	// $my->setPage(2)->setPageRow(2)->pageNation();

	// 리스트
	$row = $my->select()->get();

	// 리스트 번호
	$listNumber = $my->getListNumber();

	echo "총 게시물: ". $totalCount;
	echo "<br>";
	for ($i=0; $i<count($row); $i++) {
		echo $listNumber-- .": ";
		print_r($row[$i]);
		echo "<br>";
	}
	echo "<br>";


	// 방식3
	$my->setTable("
		memberTest as mb left join
		productTest as pd on mb.mbId=pd.mbId
	");
	// $my->setColumn("*");
	$my->setWhere("mb.mbSeq=?", "6");
	$my->setOrderBy("mb.mbSeq desc");

	// $my->setDebug(); // 쿼리로그 찍어보기
	$row = $my->select()->get();

	echo "<br>";
	for ($i=0; $i<count($row); $i++) {
		print_r($row[$i]); echo "<br>";
	}
	echo "<br>";


	// 방식4
	$my->setTable("
		memberTest as mb left join
		productTest as pd on mb.mbId=pd.mbId
	");
	$my->setColumn("mb.mbSeq");
	$my->setColumn("mb.mbId");
	$my->setColumn("mb.mbName as name, pd.pdTitle");
	$my->setColumn("(select pdTitle from productTest as pp where pp.mbId=mb.mbId) as pMbId");
	$my->setColumn("(select pdTitle from productTest as pp where pp.pdId=? limit 1) as ppPdId", "1");
	$my->setColumn("(select pdTitle from productTest as pp where pp.pdId=? or pp.pdId=? limit 1) as ppPdIdArray", array("2", "3"));
	$my->setWhere("mb.mbSeq=?", "6");
	$my->setOrderBy("mb.mbId desc");

	// $my->setDebug(); // 쿼리로그 찍어보기
	$row = $my->select()->get();

	echo "<br>";
	for ($i=0; $i<count($row); $i++) {
		print_r($row[$i]); echo "<br>";
	}
	echo "<br>";


	// 방식5
	$my->setTable("
		memberTest as mb left join
		productTest as pd on mb.mbId=pd.mbId
	");
	$my->setWhere("mb.mbId != ?", "1");
	$my->setOrderBy("mb.mbId desc");
	$my->setGroupBy("mb.mbName");
	$my->setHaving("mb.mbSeq=?", "7");
	$my->setLimit("0, 1");

	// $my->setDebug(); // 쿼리로그 찍어보기
	$row = $my->select()->get();

	echo "<br>";
	for ($i=0; $i<count($row); $i++) {
		print_r($row[$i]); echo "<br>";
	}
	echo "<br>";
?>
```
---


## 💡 debug

```php
<?php
	$my->setDebug();
?>
```
---


## 💡 error

```php
<?php
	if (!empty($my->getError())) {
		echo "에러";
		exit;
	}
?>
```
---


## 📄 DOC

#### 🌈 tran
|    Option    | Description |
| :----------- | :---------- |
| beginTran    | 트랜젝션    |
| commitTran   | 커밋        |
| rollbackTran | 롤백        |

#### 🌈 error, debug, print
|  Option  | Description |
| :------- | :---------- |
| setError | 에러        |
| setDebug | 디버그 모드 |
| setPrint | SQL문 print |

#### 🌈 default
|   Option   | Description |
| :--------- | :---------- |
| setTable   | 테이블      |
| setParam   | 파라미터    |
| setColumn  | select 컬럼 |
| setWhere   | 조건        |
| setGroupBy | 그룹        |
| setHaving  | having 조건 |
| setOrderBy | 정렬        |
| setLimit   | 제한        |

#### 🌈 seq, paging
|    Option     |     Description     |
| :------------ | :------------------ |
| getLastId     | 입력했던 데이터 seq |
| getListNumber | 리스트 번호         |
| setPage       | 현재 페이지         |
| setPageRow    | 목록 개수           |

#### 🌈 sql
| Option | Description |
| :----- | :---------- |
| insert | isnert 조합 |
| update | update 조합 |
| delete | delete 조합 |
| select | select 조합 |

#### 🌈 execute
|   Option    |    Description     |
| :---------- | :----------------- |
| execute     | 실행               |
| executeTran | 트랜젝션 포함 실행 |

#### 🌈 cms
| Option  |       Description        |
| :------ | :----------------------- |
| run     | SQL문 실행               |
| runTran | SQL문 트랜젝션 포함 실행 |
---