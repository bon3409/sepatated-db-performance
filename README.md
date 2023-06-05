# 測試讀寫分離的效能差異

## 使用到的工具

- [Docker](https://www.docker.com/)
- [Laravel](https://laravel.com/)
- [Nginx](https://www.nginx.com/)
- [MySQL](https://www.mysql.com/)
- [Jmeter](https://jmeter.apache.org/)
- [Python Pandas](https://pandas.pydata.org/)
- [Python Matplotlib](https://matplotlib.org/)

## 目的

使用 Docker Compose 建立兩個 DB，來測試單一資料庫與讀寫分離資料庫的效能。

## MySQL 同步資料庫的配置

- Master DB 設定

	```conf
	[mysqld]
	...

    # 指定 server-id
	server-id=1
	
	# 指定要同步的 DB
	binlog_do_db=multiple_test
	
	# 設定 innodb_buffer_pool_size
	innodb_buffer_pool_size=1G

    ...
	```

- Slave DB 設定

	```conf
	[mysqld]
    ...

    # 指定 server-id
	server-id=2
	
	# 指定要被同步的 DB
	binlog_do_db=multiple_test
	
	# 設定 innodb_buffer_pool_size
	innodb_buffer_pool_size=1G
	
    ...
	```

## Local 端憑證設定

> **Note**
> - [[推薦] 快速產生本地端開發用SSL憑證工具-mkcert](https://xenby.com/b/205-%E6%8E%A8%E8%96%A6-%E5%BF%AB%E9%80%9F%E7%94%A2%E7%94%9F%E6%9C%AC%E5%9C%B0%E7%AB%AF%E9%96%8B%E7%99%BC%E7%94%A8ssl%E6%86%91%E8%AD%89%E5%B7%A5%E5%85%B7-mkcert)

#### 安裝 mkcert

```bash
$ brew install mkcert
$ brew install nss
```

#### 安裝完後輸入以下指令產生root憑證，並且輸入sudo密碼

```bash
$ mkcert -install
```

會顯示訊息顯示產生的憑證位置 (不同作業系統產生的位置不同，請注意顯示的訊息)

```bash
Created a new local CA at "/home/your_username/.local/share/mkcert"
```

#### 安裝完 `mkcert` 之後，要建立憑證

##### 舉例來說，要建立 `example.jp.ngrok.io` 的憑證：

1. 開啟 terminal，建立憑證

	```bash
	$ mkcert "*.jp.ngrok.io" local 127.0.0.1 ::1
	```

2. 將兩個憑證移動到要放置的目錄

	- 預設位置：`/User/<User Name>/_wildcard.jp.ngrok.io+3-key.pem` and `/User/<User Name>/_wildcard.jp.ngrok.io+3.pem`

	- 將這兩個檔案複製到 `./nginx/` 目錄底下，之後 Docker Compose run 起來就會把憑證放進 Docker Container 內

## hosts 設定

為了在 local 可以測試，所以要在 `/etc/hosts` 裡面新增 host

```bash
$ sudo vim /etc/hosts
```

```txt
# /etc/hosts

127.0.0.1       localhost

# 測試資料庫讀寫分離
127.0.0.1       multiple-database.jp.ngrok.io  # <--- 要設定的 host
```

## 啟用 Docker

- 啟用 Docker

	```bash
	$ docker compose build
	
	$ docker compose up -d
	```

- 檢查 Docker 目前的 container

	```bash
	$ docker ps
	```

- 檢查 Docker 的資源使用狀況

	```bash
	$ docker stats

	CONTAINER ID   NAME                          CPU %     MEM USAGE / LIMIT     MEM %     NET I/O           BLOCK I/O         PIDS
	1febf0f3682c   multiple-database-nginx       0.00%     5.355MiB / 7.773GiB   0.07%     327kB / 616kB     213kB / 4.1kB     5
	5a5d63b597ef   multiple-database-master-db   2.51%     482.3MiB / 7.773GiB   6.06%     12.4MB / 21.5MB   3.35MB / 24.6kB   45
	fb5e2c992abc   multiple-database-slave-db    2.44%     484.8MiB / 7.773GiB   6.09%     6.25MB / 391kB    2.2MB / 24.6kB    50
	9bf4a4aa7691   multiple-database-app         0.02%     72.16MiB / 7.773GiB   0.91%     15.7MB / 12.6MB   754kB / 0B        5
	```

## 啟用資料庫的同步

```bash
$ sh setup_db.sh
```

## Laravel 設定

- 進入 container

	```bash
	# docker exec -it <container_id or name> bash
	$ docker exec -it multiple-database-app bash
	```

- 安裝 laravel

	```bash
	$ composer install
	```

- 建立 `.env`

	```bash
	$ cp .env.example .env
	```

- 建立 `APP_KEY`

	```bash
	$ php artisan key:generate
	```

- 編輯 `.env` 設定 DB 連線資訊

	```.env
	DB_CONNECTION=mysql
	DB_WRITE_HOST=multiple-database-master-db
	DB_WRITE_PORT=3306
	DB_WRITE_DATABASE=multiple_test
	DB_WRITE_USERNAME=root
	DB_WRITE_PASSWORD=root
	DB_READ_HOST=multiple-database-slave-db
	DB_READ_PORT=3306
	DB_READ_DATABASE=multiple_test
	DB_READ_USERNAME=root
	DB_READ_PASSWORD=root
	```

- 執行 Migrate

	```bash
	$ php artisan migrate
	```

- 建立假資料

	會先建立 10,000 個 user，與 100,000 筆訂單資訊

	```bash
	$ php artisan db:seed --class=FakeData
	```

## API

#### 測試單一資料庫

- 建立使用者的訂單

	```bash
	$ curl --location 'https://multiple-database.jp.ngrok.io:8443/api/single/create-order' \
		--header 'Content-Type: application/json' \
		--data '{
		    "user_id": 1
		}
	```

- 取得使用者所有訂單

	```bash
	$ curl --location 'https://multiple-database.jp.ngrok.io:8443/api/single/get-orders?user_id=1
	```

#### 測試讀寫分離資料庫

- 建立使用者的訂單

	```bash
	$ curl --location 'https://multiple-database.jp.ngrok.io:8443/api/multiple/create-order' \
		--header 'Content-Type: application/json' \
		--data '{
		    "user_id": 1
		}
	```

- 取得使用者所有訂單

	```bash
	$ curl --location 'https://multiple-database.jp.ngrok.io:8443/api/multiple/get-orders?user_id=1
	```

## Jmeter 壓測

*每次測試的初始條件: **10,000 users** 與 **100,000 筆訂單***

讀寫分離資料庫測試設定截圖

![](https://raw.githubusercontent.com/bon3409/sepatated-db-performance/master/result/jmeter_screenshot/Multiple%20database%20create%20order.png?token=GHSAT0AAAAAAB43UDJZ55ROUK7U2KOUMSS6ZDW7TUQ)

![](https://raw.githubusercontent.com/bon3409/sepatated-db-performance/master/result/jmeter_screenshot/Multiple%20database%20get%20all%20orders.png?token=GHSAT0AAAAAAB43UDJZMNTKQMVA7GFDE76UZDW7UOA)

## Python 製作比較的圖表

*根據 Jmeter 匯出的 csv 檔案進行圖表繪製*

- **一分鐘內逐步增加到 120 人查詢訂單 + 120 人建立訂單**

	![](https://raw.githubusercontent.com/bon3409/sepatated-db-performance/master/result/120_times_in_60_seconds/120_times_in_60_seconds_result.png?token=GHSAT0AAAAAAB43UDJZYEE6VVZYI7V7ZJFOZDW7U6A)

- **一分鐘內逐步增加到 180 人查詢訂單 + 180 人建立訂單**

	![](https://raw.githubusercontent.com/bon3409/sepatated-db-performance/master/result/180_times_in60_seconds/180_times_in_60_seconds_result.png?token=GHSAT0AAAAAAB43UDJZZMETTMYGTNJVHFLOZDW7VRA)

- **一分鐘內逐步增加到 72 人查詢訂單，48 人建立訂單 (讀寫比例 6:4)**

	- 單一資料庫

	    ![](https://raw.githubusercontent.com/bon3409/sepatated-db-performance/master/result/write_4_read_6/single_database_result.png?token=GHSAT0AAAAAAB43UDJYO4ZSIVWG2GZB7NOYZDW7V4Q)

	- 讀寫分離資料庫

	    ![](https://raw.githubusercontent.com/bon3409/sepatated-db-performance/master/result/write_4_read_6/multiple_database_result.png?token=GHSAT0AAAAAAB43UDJZMT54WWLGM45Y3VS6ZDW7WDA)

- **一分鐘內逐步增加到 96 人查詢訂單，24 人建立訂單 (讀寫比例 8:2)**

	- 單一資料庫

	    ![](https://raw.githubusercontent.com/bon3409/sepatated-db-performance/master/result/write_2_read_8/single_database_result.png?token=GHSAT0AAAAAAB43UDJZ4QPP2CLHBAL552ACZDW7WLQ)

	- 讀寫分離資料庫

	    ![](https://raw.githubusercontent.com/bon3409/sepatated-db-performance/master/result/write_2_read_8/multiple_database_result.png?token=GHSAT0AAAAAAB43UDJZURX35UBI42OT3MXQZDW7WTQ)

## 總結

經過測試之後，讀寫分離的資料庫在效能上，會比單一資料庫好一點，但因為是在 local 進行測試，所以多少還是會受到電腦資源分配的影響，有時候測試出來的數據會偏差的比較明顯，因此這樣的數據就不會採納。

## 參考資料

> **Note**
> - [How to create Master-Slave MySQL 8 with docker-compose.yml](https://pierreabreu.medium.com/how-to-create-master-slave-mysql-8-with-docker-compose-yml-c137f45e28c7)
