# SOSBoard

**[한국어](#한국어)** | **[English](#english)**

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
- **보안**: CSRF 토큰, 스팸 방지(허니팟 + 최소 작성 시간 + 요청 빈도 제한), 제목/내용/닉네임에
  태그(`<`,`>`) 입력 차단(출력 시 이스케이프도 항상 적용), 비밀번호 bcrypt 해시, soft delete
- **성능**: CSS를 매 요청 HTML에 인라인 삽입해 페이지당 HTTP 요청 1개로 유지(저대역폭 회선 대응),
  gzip 압축, keyset 페이지네이션(대량 데이터에서도 빠름)

### 기술 스택

PHP 8.1+ (프레임워크 없이 경량 자체 라우터) · PDO(MySQL/MariaDB) · 순수 CSS · JavaScript 없음

### 요구 사항

- PHP **8.1 이상** (`never` 반환 타입 등 8.1 문법을 사용합니다)
- PHP 확장: `pdo_mysql`, `mbstring`, `openssl`
- MySQL 5.7+ 또는 MariaDB 10.x (FULLTEXT 인덱스 지원 필요)
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

# 4. config/config.php에서 DB 접속정보, security.ip_pepper, app.debug(운영 시 false) 수정
```

기본 관리자 계정은 `admin` / `ChangeMe123!` 입니다. **로그인 후 반드시 비밀번호를 바꾸거나, 배포
전에 새 관리자 계정을 만들고 이 계정은 삭제하세요.**

### 디렉터리 구조

```
/index.php            프론트 컨트롤러 (라우팅, 매 요청 style.css를 읽어 인라인 삽입)
/.htaccess             rewrite + 보안 헤더 + 디렉터리 차단
/style.css              CSS 원본 소스 (브라우저에 직접 서빙되지 않고 index.php가 읽어서 인라인)
/config/config.php      앱 설정 (DB, 세션, 보안, 제한값) — 웹 접근 차단됨
/src/Lib/                Db, Session, Csrf, I18n, Auth, View, Validator, RateLimit, Url, Dates,
                         Phone, Countries, Config
/src/Repository/         UserRepository, PostRepository, ContactRepository (PDO)
/src/Controller/         BoardController, AuthController, ContactController
/src/Views/               board/, contact/, auth/, partials/
/lang/ko.php,en.php,ja.php   번역 리소스 — 웹 접근 차단됨
/sql/migrations/          스키마 마이그레이션 — 웹 접근 차단됨
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

### 알려진 제약사항

- 실제 피처폰/에뮬레이터 실기 검증은 하지 않았습니다.
- HTTPS/TLS 정책 미정 — 레거시 단말은 최신 인증서를 검증하지 못하는 경우가 많아, 평문 HTTP
  병행 여부와 로그인 기능의 위험 감수 여부를 배포 전에 정해야 합니다.
- 게시판 검색은 사용하는 MariaDB에 CJK용 ngram 파서가 없어 **단어 맨 앞부분만** 매칭합니다
  (예: "실종"으로 검색하면 "실종자를 찾습니다"는 찾지만 "종자"로는 못 찾음).
- 신고/블라인드 등 모더레이션 기능은 아직 없습니다(soft delete만 있어 이후 추가는 쉬운 구조).
- SQLite 이식은 Repository 계층 구조상 가능하지만 실제 SQLite용 마이그레이션은 아직 없습니다.

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
- **Security**: CSRF tokens, spam mitigation (honeypot + minimum fill time + rate limiting), angle
  brackets (`<`, `>`) rejected in titles/bodies/nicknames (output is always HTML-escaped regardless),
  bcrypt password hashing, soft deletes
- **Performance**: CSS is inlined into every response (no separate stylesheet request — one HTTP
  request per page, which matters on high-latency connections), gzip compression, keyset pagination
  that stays fast at scale

### Tech stack

PHP 8.1+ (no framework — a small hand-rolled router) · PDO (MySQL/MariaDB) · plain CSS · zero JavaScript

### Requirements

- PHP **8.1+** (the code uses 8.1 syntax such as the `never` return type)
- PHP extensions: `pdo_mysql`, `mbstring`, `openssl`
- MySQL 5.7+ or MariaDB 10.x (FULLTEXT index support required)
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

# 4. Edit config/config.php: DB credentials, security.ip_pepper, and set app.debug to false in production
```

The seeded admin account is `admin` / `ChangeMe123!`. **Change this password after first login,
or create a fresh admin account and delete this one before deploying anywhere real.**

### Directory structure

```
/index.php            Front controller (routing; reads style.css and inlines it into every response)
/.htaccess             Rewrite rules + security headers + directory blocking
/style.css              Source CSS (never served directly — index.php reads and inlines it)
/config/config.php      App config (DB, session, security, limits) — blocked from web access
/src/Lib/                Db, Session, Csrf, I18n, Auth, View, Validator, RateLimit, Url, Dates,
                         Phone, Countries, Config
/src/Repository/         UserRepository, PostRepository, ContactRepository (PDO)
/src/Controller/         BoardController, AuthController, ContactController
/src/Views/               board/, contact/, auth/, partials/
/lang/ko.php,en.php,ja.php   Translation strings — blocked from web access
/sql/migrations/          Schema migrations — blocked from web access
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
- SQLite portability was a design goal (the Repository layer isolates SQL), but no SQLite
  migration files exist yet.
