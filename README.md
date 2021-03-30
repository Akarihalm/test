# 温泉maas API

## 参考
- Line Login IDトークン検証  
  https://developers.line.biz/ja/reference/line-login/#verify-id-token


- Line Messaging API プッシュメッセージ  
  https://developers.line.biz/ja/reference/messaging-api/#send-push-message


## API仕様

### 共通
#### URL
https://api.onsen-maas.com/api

#### Request Header

| Name          | Type      | Value       | Description                           |
| -----------   | ------    | ------------- | ------------------------------------- |
| Authorization | string    | ****** | Secret key   |
| Content-Type  | string    | application/json |    |
| Accept  | string    | application/json |    |

#### Response Header

| Name          | Type      | Value       | Description                           |
| -----------   | ------    | ------------- | ------------------------------------- |
| Content-Type  | string    | application/json |    |


### GET /points
地点情報取得API

#### Response (200)
```json
[
  {
    "key": "togura-station",
    "name": "戸倉駅", 
    "url": "https://www.shinanorailway.co.jp/area/togura.php",
    "pickUpRequiredTime": 15,
    "routeList": [
      {
        "key": "hakuchoen",
        "ticket": 1
      },
      {
        "key": "starbucks",
        "ticket": 3
      },
      {
        "key": "obasute",
        "ticket": 7
      }
    ]
  },
  {
    "key": "obasute",
    "name": "姨捨", 
    "url": "https://ja.wikipedia.org/wiki/%E5%A7%A8%E6%8D%A8%E9%A7%85",
    "pickUpRequiredTime": 30,
    "routeList": [
      {
        "key": "hakuchoen",
        "ticket": 6
      },
      {
        "key": "starbucks",
        "ticket": 4
      },
      {
        "key": "togura",
        "ticket": 7
      }
    ]
  },
  {}
]
```

### POST /reservations
配車予約API

#### Request

| Key          | Type   | Required | Sample | Description                           |
| -----------  | ------ | ---- | ------ | ------------------------------------- |
| idToken      | string | true | `aaa.bbb.ccc`    | LIFFから取得したIDトークン   |
| departureKey | enum   | true | `obasute`        | 出発地点 (地点情報取得APIから取得) |
| arrivalKey   | enum   | true | `togura-station` | 到着地点 (地点情報取得APIから取得) |
| tel          | string | true | `03-1111-2222`   | 電話番号 |
| passengerNumbers   | int | false | 3   | 乗車人数 |
| passengers   | string | false | `田村、古澤`   | 相乗りする人の名前 |

```json
{
  "idToken": "aaa.bbb.ccc",
  "departureKey": "obasute",
  "arrivalKey": "togura-station",
  "tel": "03-1111-2222",
  "passengers": "田村、古澤"
}
```

#### Response (200)

```json
{}
```


### GET /reservations
配車一覧取得API

#### Request

| Key         | Type   | Required | Default | Description                      |
| ----------- | ------ | ---- | ------ | ------------------------------------- |
| unanswered  | boolean | false | `false` | 何も返答していない配車予約のみ取得の場合は`true` |

```
GET /reservations?unanswered=true
```

#### Response (200)

```json
[
  {
    "id": "12345",
    "name": "A.Furusawa",
    "departureKey": "nakaraya",
    "arrivalKey": "starbucks",
    "reservationTime": "16:35",
    "pickUpTime": "17:05",
    "passengerNumbers": 3,
    "tel": "090-1234-5678",
    "status": ""
  },
  {}
]
```


### POST /reservations/:reservationId/answer
配車予約に対する応答API

#### Request

| Key       | Type   | Required | Default | Description                      |
| -----------  | ------ | ---- | ------ | ------------------------------------- |
| status   | enum | true |  | dispatching/cancel (配車済み／配車キャンセル) |

```
POST /reservations/12345/answer
```
```json
{
  "status": "dispatching"
}
```

#### Response (200)

```json
{}
```

### ■ ローカル環境構築
```
composer install
cp .env.example .env
```

Update `.env` file
```
vi .env
-----------
DB_DATABASE=xxxx
DB_USERNAME=xxxx
DB_PASSWORD=xxxx
APP_URL=xxxx
API_SOURCE_URL=xxxx
SECRET_TOKEN=xxxx
LINE_CLIENT_ID=xxxx
LINE_CHANNEL_ACCESS_TOKEN=xxxx
-----------
```

```
chmod 777 storage/logs
chmod 777 storage/framework/views
php artisan key:generate
php artisan storage:link
php artisan migrate
```

### ■ 地点登録
```
php artisan db:seed
```
