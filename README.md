# SOSBoard

**[한국어](#한국어)** | **[English](#english)** | **[日本語](#日本語)**

---

## 한국어

2008년 이후 출시된 Wi-Fi 탑재 피처폰부터 최신 브라우저까지 함께 지원하는 것을 목표로 만든
다국어(한국어/영어/일본어) 재난·긴급 상황 게시판입니다. JavaScript 없이 서버 사이드 렌더링과
최소 CSS만으로 동작하도록 설계해, 저사양·저대역폭 환경에서도 페이지가 뜨도록 만들었습니다.

### 주요 기능

- **재난 대응 카테고리**: 구조요청 · 안전확인 · 실종 · 재난정보 · 자유 · 공지(관리자 전용)
- **회원/비회원 글쓰기 병행**: 계정 로그인 또는 닉네임+비밀번호만으로 익명 글쓰기 가능
- **제목/내용 검색**: MySQL/MariaDB FULLTEXT 인덱스 기반 접두어 검색 (LIKE 전체 스캔 대신 인덱스 사용)
- **연락게시판**(`/contact`): 일반 게시판과 별도로, **전화번호 + 200자 이내 내용**만 남기는
  긴급 연락용 게시판. 국가번호 선택(23개국), 목록에서는 번호 일부 마스킹, 완전일치 검색 지원
- **다국어**: URL 경로 프리픽스(`/ko`, `/en`, `/ja`) + `Accept-Language` 자동 감지 + 수동 전환
- **보안**: 모든 출력은 예외 없이 이스케이프(`View::e()`), 모든 상태 변경 요청에 CSRF 토큰,
  글쓰기/로그인/회원가입 전부 IP·계정 기준 요청 빈도 제한, 스팸 방지(허니팟 + 최소 작성 시간 —
  세션 마커가 없으면 검사를 통과가 아니라 실패로 처리), 제목/내용/닉네임 태그(`<`,`>`) 입력 차단,
  비밀번호 bcrypt 해시 + 존재하지 않는 계정에도 동일한 타이밍으로 응답(계정 존재 여부 추측 방지),
  모든 SQL은 PDO 준비된 구문만 사용, soft delete. 2026-08-16에 전체 코드베이스를 직접 감사해서
  회원가입에 빈도 제한이 빠져있던 것과 최소 작성 시간 검사를 우회할 수 있던 로직을 찾아 고쳤고,
  이어서 오픈 리다이렉트·Host 헤더 주입·경로 순회·세션 쿠키 속성까지 범위를 넓혀 재점검했지만
  추가로 발견된 건 없었습니다. CSP에는 `object-src 'none'`도 추가했습니다.
- **관리자 모더레이션**: 사전 rate limit 대신 사후 대응 — 관리자가 최근 글을 IP와 함께 보고
  즉시 IP 차단+글 삭제, 또는 범위 차단 가능. 이 때문에 IP를 이제 평문으로 저장합니다(자세한
  내용과 트레이드오프는 아래 "관리자 모더레이션 / IP 차단" 참고).
- **성능**: CSS를 매 요청 HTML에 인라인 삽입해 페이지당 HTTP 요청 1개로 유지(저대역폭 회선 대응),
  gzip 압축, keyset 페이지네이션(대량 데이터에서도 빠름), **HTML/CSS 최소화**(템플릿 가독성을
  위해 쓴 개행·들여쓰기를 응답 직전에 제거 — `config/config.php`의 `app.minify_html`로 켜고 끌 수
  있고 기본값은 켜짐. gzip이 이미 반복되는 공백을 잘 압축해줘서 gzip 이후 크기는 소폭만
  줄지만, gzip을 아예 안 보내는(`Accept-Encoding` 미지원) 구형 브라우저에는 이게 유일하게
  체감되는 절감입니다). 56Kbps 기준 실측: 각 페이지가 1.2~2KB(gzip 후)로 압축되어 0.2~0.3초
  안에 전송됩니다 — 아래 "성능 실측" 참고.

### 기술 스택

PHP 8.1+ (프레임워크 없이 경량 자체 라우터) · PDO(MySQL/MariaDB) · 순수 CSS · JavaScript 없음

### 요구 사항

- PHP **8.1 이상** (`never` 반환 타입 등 8.1 문법을 사용합니다)
- PHP 확장: `pdo_mysql`, `mbstring`, `openssl`
- MySQL 5.7+ / MariaDB 10.x 또는 SQLite 3.9+ (FTS5 필요 — 소형/저전력 하드웨어에서 별도 DB
  서버 없이 돌리고 싶을 때. 아래 "SQLite 지원" 참고)
- Apache 2.4 + `mod_rewrite`, `mod_deflate`, `mod_expires`, **`AllowOverride All`**
  (보안 모델이 `.htaccess`의 디렉터리 차단에 의존합니다 — 아래 참고)

### 설치

```bash
# 1. 저장소를 웹서버 문서 루트에 배치 (예: Apache DocumentRoot)
git clone https://github.com/<your-account>/sosboard.git

# 2. 데이터베이스 생성
mysql -u root -e "CREATE DATABASE sosboard CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

# 3. 마이그레이션 적용 (반드시 --default-character-set=utf8mb4 지정 — 안 하면 한글/일본어가 깨진 채로 저장됨)
for f in sql/migrations/*.sql; do
  mysql --default-character-set=utf8mb4 -u root sosboard < "$f"
done

# 4. config/config.php에서 DB 접속정보, app.debug(운영 시 false) 수정
```

기본 관리자 계정은 `admin` / `ChangeMe123!` 입니다. **로그인 후 반드시 비밀번호를 바꾸거나, 배포
전에 새 관리자 계정을 만들고 이 계정은 삭제하세요.**

### 관리자 모더레이션 / IP 차단 (중요한 개인정보 처리방침 변경)

이 게시판은 응급 상황용이라, 아무 글이나 못 올리게 막는 사전 rate limit을 너무 빡빡하게 걸면 오히려
진짜 급한 글을 막을 수 있습니다. 그래서 **사전 차단 대신 사후 대응**을 택했습니다: 관리자가
`/{lang}/admin`(로그인한 관리자에게만 네비게이션에 노출됨)에서 최근 게시글/연락게시판 글을 IP와
함께 보고, 문제 있는 IP를 발견하면 그 자리에서 "차단+글 삭제"를 누르거나, 시작/끝 IP를 직접 입력해
범위로 차단할 수 있습니다.

**이걸 가능하게 하려고 IP 저장 방식을 바꿨습니다.** 원래는 IP를 절대 원본으로 저장하지 않고
HMAC 해시로만 저장했는데(익명화), 해시는 특성상 "이 IP가 범위 안에 있는지"를 판단할 수 없어서
범위 차단이 아예 불가능했습니다. 그래서 **이제 게시글/연락게시판 글에 IP를 그대로(평문) 저장**합니다
— 관리자는 모더레이션 페이지에서 실제 IP를 보게 됩니다. 차단된 IP로는 글쓰기(게시판/연락게시판)와
회원가입이 모두 막힙니다(로그인은 막지 않습니다 — 같은 IP를 쓰는 무고한 기존 회원까지 계정에서
쫓아낼 이유는 없어서). 차단 시 사용자에게는 "차단됐다"는 사실을 굳이 알리지 않고 일반적인 요청
빈도 제한 문구를 보여줍니다.

**통신사 NAT 등으로 여러 사람이 같은 IP를 공유할 수 있다는 점을 꼭 감안하세요** — IP 하나를
차단하면 그 IP를 쓰는 다른 무고한 사람도 같이 막힐 수 있습니다. 모더레이션 페이지에서 언제든
바로 해제할 수 있게 만들어뒀습니다.

### 디렉터리 구조

```
/index.php            프론트 컨트롤러 (라우팅, 매 요청 style.css를 읽어 인라인 삽입)
/.htaccess             rewrite + 보안 헤더 + 디렉터리 차단
/style.css              CSS 원본 소스 (브라우저에 직접 서빙되지 않고 index.php가 읽어서 인라인)
/config/config.php      앱 설정 (DB, 세션, 보안, 제한값) — 웹 접근 차단됨
/src/Lib/                Db, Session, Csrf, I18n, Auth, View, Validator, RateLimit, Url, Dates,
                         Phone, Countries, Config, Minify
/src/Repository/         UserRepository, PostRepository, ContactRepository (PDO)
/src/Controller/         BoardController, AuthController, ContactController
/src/Views/               board/, contact/, auth/, partials/
/lang/ko.php,en.php,ja.php   번역 리소스 — 웹 접근 차단됨
/sql/migrations/          스키마 마이그레이션(*.mysql.sql / *.sqlite.sql) — 웹 접근 차단됨
```

`config`, `src`, `lang`, `sql`, `var` 디렉터리는 각각 `.htaccess`로 `Require all denied`
처리되어 브라우저에서 직접 접근할 수 없습니다. 실서버 배포 시에는 vhost의 DocumentRoot를 별도
`public/` 폴더로 지정하고 나머지를 웹 루트 밖으로 옮기는 것이 더 안전합니다.

### 라즈베리파이 / Linux 배포 시 확인할 점

- **PHP 8.1 이상 필요**: Raspberry Pi OS Bookworm(기본 PHP 8.2)은 문제없지만, 구버전 Bullseye
  (기본 PHP 7.4)는 서드파티 저장소로 PHP를 올려야 합니다.
- **`AllowOverride All` 필수**: Debian 계열 Apache 기본값은 `AllowOverride None`이라, 그대로
  두면 `.htaccess`가 무시되면서 `/sql/migrations/*.sql`(관리자 비밀번호 해시 포함) 등 보호
  디렉터리가 그대로 노출됩니다.
- 필요 apt 패키지 예시: `apache2 php php-mysql php-mbstring mariadb-server`,
  `a2enmod rewrite deflate expires` 로 모듈 활성화 필요.

### 성능 실측 (56Kbps 시뮬레이션)

2026-08-16에 gzip이 실제로는 꺼져 있던 걸 발견해서(`mod_deflate`/`mod_filter`가 이 XAMPP의
`httpd.conf`에 기본 주석 처리되어 있었음 — 로컬 환경 이슈, 리포지토리 코드와는 무관) 켠 뒤 측정한
수치입니다. 56Kbps = 초당 7,000바이트로 계산했습니다.

| 페이지 | 원본 | gzip 후 | 절감률 | 56K 전송 시간 |
|---|---:|---:|---:|---:|
| 게시판 목록 | 5,554 B | 1,742 B | 69% | 0.25s |
| 글쓰기 폼 | 4,134 B | 1,742 B | 58% | 0.25s |
| 글 상세보기 | 3,412 B | 1,557 B | 54% | 0.22s |
| 연락게시판 목록 | 4,684 B | 1,929 B | 59% | 0.28s |
| 연락게시판 글쓰기 | 4,863 B | 1,998 B | 59% | 0.29s |
| 로그인 | 3,252 B | 1,362 B | 58% | 0.19s |

페이지당 요청이 1개뿐이라(CSS 인라인 삽입) 실제 체감 로딩 시간은 위 전송 시간에 다이얼업 모뎀의
연결 지연(왕복 약 0.15~0.2초)을 더한 수준 — 즉 어떤 페이지든 대략 0.4~0.5초 안에 화면이 뜰
것으로 예상됩니다. 참고로 게시판 목록은 항상 10건만 보여주므로(keyset 페이지네이션), 게시글이
6만 건이든 10건이든 이 페이지 크기는 그대로입니다.

**회선 노이즈 감안 (실효 속도 30%, 약 16.8Kbps = 초당 2,100바이트)**: 실제 아날로그 회선은
잡음·재전송 때문에 표기 속도의 100%가 나오는 경우가 거의 없습니다. 30% 가정으로 재계산:

| 페이지 | gzip 후 | 30% 실효 전송 시간 |
|---|---:|---:|
| 게시판 목록 | 1,742 B | 0.83s |
| 글쓰기 폼 | 1,742 B | 0.83s |
| 글 상세보기 | 1,559 B | 0.74s |
| 연락게시판 목록 | 1,929 B | 0.92s |
| 연락게시판 글쓰기 | 2,000 B | 0.95s |
| 로그인 | 1,361 B | 0.65s |

노이즈가 있는 회선은 연결 지연도 보통 더 나쁘므로(재전송·지터), 연결 지연을 넉넉하게 ~0.3~0.4초로
잡으면 **체감 로딩 시간은 대략 1.1~1.3초** — 여전히 사용 가능한 수준입니다. `curl --limit-rate
2100`으로 실제 전송을 시뮬레이션해도(0.64~0.65초, 이론치보다 약간 빠름 — curl 레이트리밋의 초기
버스트 허용 때문으로 보임) 같은 자릿수로 확인됩니다.

**한 가지 남은 약점**: Apache `mod_deflate`는 HTTP 상태코드가 2xx가 아닌 응답(404, 500 등)은
기본적으로 압축하지 않습니다(Apache 자체 기본 동작). 우리 404 페이지는 2,824바이트로, 압축됐다면
더 작았을 것 — 다만 에러 페이지는 자주 발생하지 않고 크기도 크지 않아 우선순위는 낮게 뒀습니다.

### SQLite 지원 (소형/저전력 하드웨어용)

라즈베리파이보다도 더 작은 하드웨어에서는 별도 MySQL/MariaDB 서버를 띄우는 것 자체가 부담일 수
있습니다. `config/config.php`의 `db` 설정을 SQLite로 바꾸면 별도 DB 서버 없이 파일 하나로
동작합니다:

```php
'db' => [
    'driver' => 'sqlite',
    'dsn' => 'sqlite:' . __DIR__ . '/../var/data/sosboard.sqlite',
    'user' => null,
    'pass' => null,
],
```

마이그레이션은 `sql/migrations/*.sqlite.sql`(MySQL용과 파일명만 `.mysql.sql` → `.sqlite.sql`로
다름, 번호는 1:1 대응)을 순서대로 적용하면 됩니다. `var/data/`는 `.htaccess`로 웹 접근이
차단되어 있고, DB 파일 자체는 `.gitignore`에 등록되어 커밋되지 않습니다.

**MySQL과 다른 점 — 게시판 검색**: MySQL의 FULLTEXT 대신 SQLite의 FTS5 가상 테이블
(`posts_fts`)을 씁니다. `posts` 테이블에 대한 INSERT/UPDATE/DELETE를 트리거로 자동 동기화하며,
검색 방식(단어 접두어만 매칭)과 그 트레이드오프는 MySQL 쪽과 동일합니다. 연락게시판의 전화번호
검색(완전일치, `REPLACE()` 기반 정규화)과 rate limiting(요청 빈도 제한)은 MySQL 전용 SQL
문법을 전혀 쓰지 않아서 코드 변경 없이 그대로 동작합니다.

이 저장소를 만들면서 실제로 SQLite DB를 생성하고 마이그레이션을 적용한 뒤, 글쓰기·검색(FTS5
접두어 매칭 포함)·카테고리 필터·연락게시판 등록/완전일치 검색/앞자리 0 제거·로그인·회원가입까지
전부 실기로 검증했습니다.

### 알려진 제약사항

- 실제 피처폰/에뮬레이터 실기 검증은 하지 않았습니다.
- HTTPS/TLS 정책 미정 — 레거시 단말은 최신 인증서를 검증하지 못하는 경우가 많아, 평문 HTTP
  병행 여부와 로그인 기능의 위험 감수 여부를 배포 전에 정해야 합니다.
- 게시판 검색은 사용하는 MariaDB에 CJK용 ngram 파서가 없어 **단어 맨 앞부분만** 매칭합니다
  (예: "실종"으로 검색하면 "실종자를 찾습니다"는 찾지만 "종자"로는 못 찾음).
- 신고/블라인드 등 모더레이션 기능은 아직 없습니다(soft delete만 있어 이후 추가는 쉬운 구조).
- (SQLite는 이제 지원됩니다 — 위 "SQLite 지원" 참고. 남은 건 실서버 SQLite 배포 시의 백업/운영 경험 정도입니다.)

---

## English

A multilingual (Korean/English/Japanese) disaster & emergency information board, built to work
on everything from Wi-Fi-capable feature phones released after 2008 to modern browsers. It's
server-rendered with no JavaScript and minimal CSS, so pages stay usable on low-end devices and
slow connections.

### Features

- **Disaster-oriented categories**: SOS/rescue, safety check-in, missing person, disaster info,
  free chat, and admin-only notices
- **Member and guest posting**: post with an account, or anonymously with just a nickname + password
- **Title/body search**: uses a MySQL/MariaDB FULLTEXT index with prefix matching (a leading-wildcard
  `LIKE` can't use an index, so this keeps search fast as the board grows)
- **Contact board** (`/contact`): a separate board where a post is just **a phone number + up to
  200 characters** — country-code picker (23 countries), numbers masked in listings, exact-match search
- **i18n**: URL path prefixes (`/ko`, `/en`, `/ja`) + `Accept-Language` auto-detection + manual switch
- **Security**: every output is escaped without exception (`View::e()`), CSRF tokens on every
  state-changing request, rate limiting on posting/login/registration (IP- and account-based),
  spam mitigation (honeypot + minimum fill time — a missing session marker fails the check
  instead of passing it), angle brackets (`<`, `>`) rejected in titles/bodies/nicknames, bcrypt
  password hashing with constant-time responses for nonexistent accounts (no timing oracle for
  username enumeration), every SQL query goes through PDO prepared statements, soft deletes.
  A full self-audit on 2026-08-16 found and fixed two real gaps: registration had no rate
  limiting at all, and the minimum-fill-time check could be silently bypassed by skipping the
  initial page load. A broader follow-up pass (open redirect, Host-header injection, path
  traversal, session cookie attributes) found nothing further. Also added `object-src 'none'`
  to the CSP.
- **Admin moderation**: reactive rather than preemptive — an admin sees recent posts with the
  poster's IP and can ban-and-delete a single IP or a whole range on the spot. This is why IPs
  are now stored in plain text (details and the trade-off are under "Admin moderation / IP bans"
  below).
- **Performance**: CSS is inlined into every response (no separate stylesheet request — one HTTP
  request per page, which matters on high-latency connections), gzip compression, keyset pagination
  that stays fast at scale, and **HTML/CSS minification** (the line breaks and indentation used
  for readable templates are stripped right before sending — toggle via `app.minify_html` in
  `config/config.php`, on by default. Gzip already compresses repeated whitespace well, so the
  post-gzip saving is modest, but for old browsers that don't send `Accept-Encoding: gzip` at
  all, this is the only saving that reaches them). Measured at simulated 56Kbps: every page
  compresses to 1.2-2KB and transfers in 0.2-0.3s — see "Measured performance" below.

### Tech stack

PHP 8.1+ (no framework — a small hand-rolled router) · PDO (MySQL/MariaDB) · plain CSS · zero JavaScript

### Requirements

- PHP **8.1+** (the code uses 8.1 syntax such as the `never` return type)
- PHP extensions: `pdo_mysql`, `mbstring`, `openssl`
- MySQL 5.7+ / MariaDB 10.x, or SQLite 3.9+ (FTS5 required — for small/low-power hardware where
  running a separate database server isn't practical; see "SQLite support" below)
- Apache 2.4 with `mod_rewrite`, `mod_deflate`, `mod_expires`, and **`AllowOverride All`**
  (the security model depends on `.htaccess` directory blocking — see below)

### Setup

```bash
# 1. Clone into your web server's document root
git clone https://github.com/<your-account>/sosboard.git

# 2. Create the database
mysql -u root -e "CREATE DATABASE sosboard CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

# 3. Apply migrations (always pass --default-character-set=utf8mb4, otherwise multi-byte
#    text like Korean/Japanese gets corrupted on import)
for f in sql/migrations/*.sql; do
  mysql --default-character-set=utf8mb4 -u root sosboard < "$f"
done

# 4. Edit config/config.php: DB credentials, and set app.debug to false in production
```

The seeded admin account is `admin` / `ChangeMe123!`. **Change this password after first login,
or create a fresh admin account and delete this one before deploying anywhere real.**

### Admin moderation / IP bans (a meaningful privacy-policy change)

This board is for emergencies, so tight preemptive rate limits risk delaying a genuine urgent
post. We chose **reactive moderation instead of preemptive blocking**: an admin (the nav link
only shows up when logged in as one) reviews recent board/contact posts at `/{lang}/admin` along
with the poster's IP, and can either ban-and-delete a single offending IP on the spot, or type in
a start/end IP range to ban directly.

**Making this possible required changing how IPs are stored.** IPs used to never be stored raw —
only an HMAC hash (anonymized) — but a hash can't tell you whether an IP falls inside a range, so
range bans were simply impossible. **Posts and contact-board entries now store the IP in plain
text**, and an admin sees the real address on the moderation page. A banned IP is blocked from
posting (board and contact) and from registering a new account (login is not blocked — no reason
to lock an innocent existing member out of their own account just because they share an IP with
someone abusive). The rejection message doesn't reveal that it's specifically a ban, just the
same generic rate-limit wording.

**Keep in mind that multiple people can share one IP** (carrier-grade NAT, for instance) —
banning an IP can catch innocent people using the same one. The moderation page lets you unban
instantly if that happens.

### Directory structure

```
/index.php            Front controller (routing; reads style.css and inlines it into every response)
/.htaccess             Rewrite rules + security headers + directory blocking
/style.css              Source CSS (never served directly — index.php reads and inlines it)
/config/config.php      App config (DB, session, security, limits) — blocked from web access
/src/Lib/                Db, Session, Csrf, I18n, Auth, View, Validator, RateLimit, Url, Dates,
                         Phone, Countries, Config, Minify
/src/Repository/         UserRepository, PostRepository, ContactRepository (PDO)
/src/Controller/         BoardController, AuthController, ContactController
/src/Views/               board/, contact/, auth/, partials/
/lang/ko.php,en.php,ja.php   Translation strings — blocked from web access
/sql/migrations/          Schema migrations (*.mysql.sql / *.sqlite.sql) — blocked from web access
```

The `config`, `src`, `lang`, `sql`, and `var` directories each carry a `.htaccess` with
`Require all denied`, so they can't be requested directly. For a production deployment, it's
safer to point the vhost's DocumentRoot at a dedicated `public/` folder and keep everything
else outside the web root entirely.

### Deploying on Raspberry Pi / Linux

- **Requires PHP 8.1+.** Raspberry Pi OS Bookworm (ships PHP 8.2 by default) works out of the
  box; older Bullseye (PHP 7.4 by default) needs a third-party repo for a newer PHP.
- **`AllowOverride All` is required.** Debian's default Apache vhost usually has
  `AllowOverride None`, which silently disables `.htaccess` — leaving protected paths like
  `/sql/migrations/*.sql` (which contains the admin password hash) publicly downloadable.
- Typical apt packages: `apache2 php php-mysql php-mbstring mariadb-server`, then
  `a2enmod rewrite deflate expires` to enable the required modules.

### Measured performance (simulated 56Kbps)

Measured on 2026-08-16, after discovering gzip wasn't actually active (`mod_deflate`/`mod_filter`
were commented out by default in this XAMPP install's `httpd.conf` — a local environment issue,
unrelated to the repository code) and enabling it. 56Kbps = 7,000 bytes/sec.

| Page | Raw | Gzipped | Reduction | Transfer @56K |
|---|---:|---:|---:|---:|
| Board list | 5,554 B | 1,742 B | 69% | 0.25s |
| Write form | 4,134 B | 1,742 B | 58% | 0.25s |
| Post detail | 3,412 B | 1,557 B | 54% | 0.22s |
| Contact board list | 4,684 B | 1,929 B | 59% | 0.28s |
| Contact write form | 4,863 B | 1,998 B | 59% | 0.29s |
| Login | 3,252 B | 1,362 B | 58% | 0.19s |

Since there's only one HTTP request per page (CSS is inlined), real-world load time is roughly
the transfer time above plus a dial-up modem's connection latency (~0.15-0.2s round trip) — so
any page should render in about 0.4-0.5s total. The board list always shows exactly 10 posts
(keyset pagination), so this page size stays constant whether the board has 10 posts or 60,000.

**Accounting for line noise (30% effective throughput, ~16.8Kbps = 2,100 bytes/sec)**: a real
analog line rarely sustains 100% of its rated speed due to noise and retransmissions. Recomputed
at 30%:

| Page | Gzipped | Transfer @30% effective |
|---|---:|---:|
| Board list | 1,742 B | 0.83s |
| Write form | 1,742 B | 0.83s |
| Post detail | 1,559 B | 0.74s |
| Contact board list | 1,929 B | 0.92s |
| Contact write form | 2,000 B | 0.95s |
| Login | 1,361 B | 0.65s |

A noisy line also usually means worse connection latency (retransmits, jitter); budgeting a more
generous ~0.3-0.4s for that puts **real-world load time at roughly 1.1-1.3s** — still entirely
usable. An actual simulated transfer via `curl --limit-rate 2100` lands in the same ballpark
(0.64-0.65s, somewhat faster than the pure math — likely curl's rate limiter allowing an initial burst).

**One remaining weak spot**: Apache's `mod_deflate` doesn't compress non-2xx responses (404, 500,
etc.) by default — that's stock Apache behavior. Our 404 page is 2,824 bytes uncompressed; it
would be smaller compressed, but error pages are infrequent and already small, so this was left
as a low-priority item.

### SQLite support (for small/low-power hardware)

On hardware even smaller than a Raspberry Pi, running a separate MySQL/MariaDB server can be
more overhead than it's worth. Switch `config/config.php`'s `db` block to SQLite and the app
runs off a single file, no database server required:

```php
'db' => [
    'driver' => 'sqlite',
    'dsn' => 'sqlite:' . __DIR__ . '/../var/data/sosboard.sqlite',
    'user' => null,
    'pass' => null,
],
```

Apply the migrations in `sql/migrations/*.sqlite.sql` (same numbering as the MySQL ones, just
`.mysql.sql` swapped for `.sqlite.sql`), in order. `var/data/` is blocked from web access via
`.htaccess`, and the database file itself is gitignored so it's never committed.

**What's different from MySQL — board search**: instead of MySQL's FULLTEXT, this uses SQLite's
FTS5 virtual table (`posts_fts`), kept in sync with `posts` via triggers on insert/update/delete.
The search behavior (prefix-only matching) and its trade-off are identical to the MySQL side.
The contact board's phone search (exact match, `REPLACE()`-based normalization) and rate
limiting use no MySQL-specific SQL at all, so they work unchanged on SQLite.

While building this, an actual SQLite database was created, migrated, and exercised end to end:
posting, search (including FTS5 prefix matching), category filtering, contact board registration/
exact-match search/leading-zero stripping, login, and registration all verified working.

### Known limitations

- Not yet tested on real feature-phone hardware or emulators.
- HTTPS/TLS policy is undecided — legacy devices often can't validate modern certificates, so
  whether to serve plain HTTP alongside HTTPS (and how that interacts with login) needs a decision
  before any real deployment.
- Board search only matches from the **start** of a word, because the MariaDB build in use has
  no CJK ngram parser (searching "실종" finds "실종자를 찾습니다" but "종자" — a mid-word
  substring — won't match).
- No moderation tools yet (report/hide) — the schema already supports soft deletes, so adding
  this later is straightforward.
- (SQLite is now supported — see "SQLite support" above. What's left is real-world operational
  experience running it in production: backups, etc.)

---

## 日本語

2008年以降に発売されたWi-Fi対応フィーチャーフォンから最新ブラウザまで、幅広い端末で動作することを
目指して作った多言語(韓国語/英語/日本語)の災害・緊急情報掲示板です。JavaScriptを使わずサーバー
サイドレンダリングと最小限のCSSだけで動作するように設計しており、低スペック端末や低速回線でも
ページが表示されます。

### 主な機能

- **災害対応カテゴリ**: 救助要請・安否確認・行方不明・災害情報・自由・お知らせ(管理者専用)
- **会員/非会員投稿の併用**: アカウントでログインするか、ニックネーム+パスワードだけで匿名投稿可能
- **タイトル/本文検索**: MySQL/MariaDBのFULLTEXTインデックスによる前方一致検索(先頭にワイルド
  カードが付く`LIKE`はインデックスを使えないため、掲示板が大きくなっても検索速度を維持できます)
- **連絡掲示板**(`/contact`): 通常の掲示板とは別に、**電話番号+200文字以内の内容**だけを残せる
  緊急連絡用の掲示板。国番号選択(23か国)、一覧では番号の一部をマスキング、完全一致検索に対応
- **多言語対応**: URLパスプレフィックス(`/ko`, `/en`, `/ja`) + `Accept-Language`自動判定 +
  手動切り替え
- **セキュリティ**: すべての出力を例外なくエスケープ(`View::e()`)、すべての状態変更リクエストに
  CSRFトークン、投稿/ログイン/会員登録すべてにIP・アカウント単位のレート制限、スパム対策
  (ハニーポット + 最短入力時間 — セッションのマーカーが無い場合はチェック通過ではなく失敗として
  扱う)、タイトル/本文/ニックネームでのタグ(`<`,`>`)入力を拒否、bcryptによるパスワードハッシュ化
  + 存在しないアカウントに対しても同じタイミングで応答(アカウントの存在推測を防止)、すべての
  SQLはPDOのプリペアドステートメントのみを使用、ソフトデリート。2026-08-16にコードベース全体を
  自己監査し、会員登録にレート制限が無かった点と最短入力時間チェックを回避できた点の2件を発見・
  修正しました。続けてオープンリダイレクト・Hostヘッダーインジェクション・パストラバーサル・
  セッションCookie属性まで範囲を広げて再点検しましたが、追加の問題は見つかりませんでした。CSPに
  `object-src 'none'`も追加しています。
- **管理者モデレーション**: 事前のレート制限ではなく事後対応 — 管理者は投稿者のIPと一緒に最近の
  投稿を確認でき、その場で単一IPまたは範囲でまとめて遮断+削除できます。このためIPは現在平文で
  保存されています(詳細とトレードオフは下記「管理者モデレーション / IP遮断」を参照)。
- **パフォーマンス**: CSSを毎リクエストHTMLにインライン挿入し、ページごとのHTTPリクエストを
  1つに抑えています(低帯域回線向け)。gzip圧縮、keysetページネーション(データ量が多くても高速)、
  **HTML/CSSの最小化**(テンプレートを読みやすくするための改行・インデントを送信直前に除去 —
  `config/config.php`の`app.minify_html`でオン/オフ切り替え可能、デフォルトはオン。gzipは
  繰り返す空白をすでにうまく圧縮するのでgzip後の削減幅は小さいですが、`Accept-Encoding: gzip`を
  送らない古いブラウザにはこれだけが届く削減になります)。56Kbpsでの実測ではページごとに圧縮後
  1.2〜2KBで、転送時間は0.2〜0.3秒 — 詳細は下記「実測パフォーマンス」を参照。

### 技術スタック

PHP 8.1+ (フレームワークなし、軽量な自作ルーター) · PDO(MySQL/MariaDB) · 素のCSS ·
JavaScriptなし

### 動作要件

- PHP **8.1以上**(`never`戻り値型など8.1の構文を使用しています)
- PHP拡張: `pdo_mysql`, `mbstring`, `openssl`
- MySQL 5.7+ / MariaDB 10.x、またはSQLite 3.9+(FTS5が必要 — 別途DBサーバーを立てるのが
  現実的でない小型・低電力ハードウェア向け。詳しくは下記「SQLiteサポート」を参照)
- Apache 2.4 + `mod_rewrite`, `mod_deflate`, `mod_expires`, **`AllowOverride All`**
  (セキュリティモデルが`.htaccess`によるディレクトリ制限に依存しています — 下記参照)

### セットアップ

```bash
# 1. Webサーバーのドキュメントルートにリポジトリを配置
git clone https://github.com/<your-account>/sosboard.git

# 2. データベースを作成
mysql -u root -e "CREATE DATABASE sosboard CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

# 3. マイグレーションを適用(必ず --default-character-set=utf8mb4 を指定 — 指定しないと
#    韓国語/日本語などのマルチバイト文字がインポート時に文字化けします)
for f in sql/migrations/*.sql; do
  mysql --default-character-set=utf8mb4 -u root sosboard < "$f"
done

# 4. config/config.php を編集: DB接続情報、本番環境ではapp.debugをfalseに
```

初期管理者アカウントは `admin` / `ChangeMe123!` です。**初回ログイン後に必ずパスワードを変更する
か、実際にデプロイする前に新しい管理者アカウントを作成してこのアカウントは削除してください。**

### 管理者モデレーション / IP遮断(個人情報の扱いに関わる重要な変更)

この掲示板は緊急時向けなので、事前のレート制限を厳しくしすぎると本当に急ぎの投稿まで妨げてしまう
恐れがあります。そこで**事前ブロックではなく事後対応**を選びました — 管理者(ログインすると
ナビゲーションにリンクが表示されます)が`/{lang}/admin`で最近の投稿・連絡掲示板の投稿を投稿者の
IPと一緒に確認し、問題のあるIPをその場で「遮断+削除」するか、開始/終了IPを直接入力して範囲で
遮断できます。

**これを可能にするため、IPの保存方法を変更しました。** 以前はIPを生のまま保存せず、HMACハッシュ
のみを保存していました(匿名化)。しかしハッシュではあるIPが特定の範囲に含まれるかどうか判定
できないため、範囲での遮断はそもそも不可能でした。**そのため現在は投稿・連絡掲示板の投稿にIPを
平文で保存**しており、管理者はモデレーションページで実際のIPアドレスを見ることになります。
遮断されたIPは投稿(掲示板・連絡掲示板)と新規会員登録の両方がブロックされます(ログインはブロック
しません — 同じIPを使っているだけの無関係な既存会員のアカウントまでロックする理由はないため)。
拒否メッセージには「遮断されている」とは明示せず、通常のレート制限と同じ文言を表示します。

**複数の人が同じIPを共有し得る点に注意してください**(キャリアのNATなど)— IPを遮断すると、同じ
IPを使っている無関係な人も巻き込んでしまう場合があります。モデレーションページからいつでもすぐに
解除できるようにしてあります。

### ディレクトリ構成

```
/index.php            フロントコントローラー(ルーティング、毎リクエストstyle.cssを読み込んでインライン化)
/.htaccess             リライトルール + セキュリティヘッダー + ディレクトリ制限
/style.css              CSSの元ソース(ブラウザに直接配信されず、index.phpが読み込んでインライン化)
/config/config.php      アプリ設定(DB、セッション、セキュリティ、制限値) — Webアクセス不可
/src/Lib/                Db, Session, Csrf, I18n, Auth, View, Validator, RateLimit, Url, Dates,
                         Phone, Countries, Config, Minify
/src/Repository/         UserRepository, PostRepository, ContactRepository (PDO)
/src/Controller/         BoardController, AuthController, ContactController
/src/Views/               board/, contact/, auth/, partials/
/lang/ko.php,en.php,ja.php   翻訳リソース — Webアクセス不可
/sql/migrations/          スキーママイグレーション(*.mysql.sql / *.sqlite.sql) — Webアクセス不可
```

`config`, `src`, `lang`, `sql`, `var` の各ディレクトリにはそれぞれ`.htaccess`で
`Require all denied`を設定しており、ブラウザから直接アクセスできません。本番環境にデプロイする
場合は、vhostのDocumentRootを専用の`public/`フォルダに向け、それ以外をWebルートの外に置く方が
より安全です。

### ラズベリーパイ / Linuxへのデプロイで確認すべき点

- **PHP 8.1以上が必要です**: Raspberry Pi OS Bookworm(デフォルトでPHP 8.2)なら問題ありませんが、
  旧バージョンのBullseye(デフォルトでPHP 7.4)ではサードパーティのリポジトリで新しいPHPを
  導入する必要があります。
- **`AllowOverride All`が必須です**: Debian系のApacheのデフォルトは`AllowOverride None`のため、
  そのままだと`.htaccess`が無視され、`/sql/migrations/*.sql`(管理者パスワードのハッシュを含む)
  などの保護対象ディレクトリがそのまま公開されてしまいます。
- 必要なaptパッケージの例: `apache2 php php-mysql php-mbstring mariadb-server`、
  `a2enmod rewrite deflate expires` でモジュールを有効化する必要があります。

### 実測パフォーマンス(56Kbpsシミュレーション)

2026-08-16に、gzipが実際には無効になっていたことが判明し(このXAMPP環境の`httpd.conf`で
`mod_deflate`/`mod_filter`がデフォルトでコメントアウトされていました — ローカル環境固有の問題で、
リポジトリのコードとは無関係)、有効化した上で測定した数値です。56Kbps = 毎秒7,000バイトとして
計算しています。

| ページ | 元サイズ | gzip後 | 削減率 | 56K転送時間 |
|---|---:|---:|---:|---:|
| 掲示板一覧 | 5,554 B | 1,742 B | 69% | 0.25s |
| 投稿フォーム | 4,134 B | 1,742 B | 58% | 0.25s |
| 投稿詳細 | 3,412 B | 1,557 B | 54% | 0.22s |
| 連絡掲示板一覧 | 4,684 B | 1,929 B | 59% | 0.28s |
| 連絡掲示板投稿フォーム | 4,863 B | 1,998 B | 59% | 0.29s |
| ログイン | 3,252 B | 1,362 B | 58% | 0.19s |

ページごとのリクエストは1つだけなので(CSSインライン化)、実際の体感読み込み時間は上記の転送時間に
ダイヤルアップモデムの接続遅延(往復約0.15〜0.2秒)を加えた程度 — つまりどのページもおよそ
0.4〜0.5秒で表示されると見込まれます。なお掲示板一覧は常に10件しか表示しないため(keyset
ページネーション)、投稿が6万件でも10件でもこのページサイズは変わりません。

**回線ノイズを考慮(実効速度30%、約16.8Kbps = 毎秒2,100バイト)**: 実際のアナログ回線は
ノイズや再送のため、表示速度の100%が出ることはほとんどありません。30%と仮定して再計算:

| ページ | gzip後 | 実効30%での転送時間 |
|---|---:|---:|
| 掲示板一覧 | 1,742 B | 0.83s |
| 投稿フォーム | 1,742 B | 0.83s |
| 投稿詳細 | 1,559 B | 0.74s |
| 連絡掲示板一覧 | 1,929 B | 0.92s |
| 連絡掲示板投稿フォーム | 2,000 B | 0.95s |
| ログイン | 1,361 B | 0.65s |

ノイズのある回線は接続遅延も通常悪化するため(再送・ジッター)、接続遅延を余裕を持って
約0.3〜0.4秒とすると、**体感読み込み時間はおよそ1.1〜1.3秒** — それでも十分実用的な範囲です。
`curl --limit-rate 2100`で実際の転送をシミュレーションしても(0.64〜0.65秒、理論値よりやや
速め — curlのレート制限が最初のバーストを許容しているためと考えられます)同じ桁の数値になります。

**残っている弱点が1つ**: Apacheの`mod_deflate`は、HTTPステータスコードが2xx以外の応答
(404、500など)をデフォルトでは圧縮しません(Apacheそのものの標準動作です)。当アプリの404
ページは2,824バイト(非圧縮)で、圧縮されればもっと小さくなりますが、エラーページは発生頻度が
低くサイズも小さいため、優先度は低いままにしています。

### SQLiteサポート(小型・低電力ハードウェア向け)

ラズベリーパイよりさらに小さいハードウェアでは、別途MySQL/MariaDBサーバーを立てること自体が
負担になる場合があります。`config/config.php`の`db`設定をSQLiteに変えれば、別のDBサーバーなしに
ファイル1つで動作します。

```php
'db' => [
    'driver' => 'sqlite',
    'dsn' => 'sqlite:' . __DIR__ . '/../var/data/sosboard.sqlite',
    'user' => null,
    'pass' => null,
],
```

マイグレーションは`sql/migrations/*.sqlite.sql`(MySQL用とファイル名の`.mysql.sql`部分だけが
`.sqlite.sql`に変わり、番号は1対1で対応)を順番に適用してください。`var/data/`は`.htaccess`で
Webアクセスを禁止しており、DBファイル自体も`.gitignore`に登録されているためコミットされません。

**MySQLとの違い — 掲示板検索**: MySQLのFULLTEXTの代わりに、SQLiteのFTS5仮想テーブル
(`posts_fts`)を使用します。`posts`テーブルへのINSERT/UPDATE/DELETEをトリガーで自動的に同期し、
検索方式(単語の先頭部分のみマッチ)とそのトレードオフはMySQL側と同じです。連絡掲示板の電話番号
検索(完全一致、`REPLACE()`による正規化)とレート制限は、MySQL固有のSQL構文を一切使っていない
ため、コード変更なしでそのまま動作します。

このリポジトリを作る過程で、実際にSQLiteのデータベースを作成してマイグレーションを適用した上で、
投稿・検索(FTS5の前方一致マッチを含む)・カテゴリ絞り込み・連絡掲示板の登録/完全一致検索/先頭の
0の除去・ログイン・会員登録まで、すべて実機で検証済みです。

### 既知の制約事項

- 実機のフィーチャーフォンやエミュレーターでの実機検証はまだ行っていません。
- HTTPS/TLSの方針は未確定です — レガシー端末は最新の証明書を検証できないことが多く、平文HTTPを
  併用するかどうか、それがログイン機能とどう関わるかを実際のデプロイ前に決める必要があります。
- 掲示板検索は、使用しているMariaDBにCJK用のngramパーサーが無いため、**単語の先頭部分のみ**
  マッチします(例: 韓国語で「실종」(行方不明)と検索すると「실종자를 찾습니다」(行方不明者を
  探しています)は見つかりますが、単語途中の部分文字列「종자」では見つかりません)。
- 通報/非表示などのモデレーション機能はまだありません(ソフトデリートの仕組みは既にあるため、
  後から追加しやすい構造です)。
- (SQLiteは現在サポートされています — 上記「SQLiteサポート」を参照。残っているのは本番環境で
  SQLiteを運用する際のバックアップなどの実運用上の経験のみです。)
