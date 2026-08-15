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
- **보안**: 모든 출력은 예외 없이 이스케이프(`View::e()`), 모든 상태 변경 요청에 CSRF 토큰,
  글쓰기/로그인/회원가입 전부 IP·계정 기준 요청 빈도 제한, 스팸 방지(허니팟 + 최소 작성 시간 —
  세션 마커가 없으면 검사를 통과가 아니라 실패로 처리), 제목/내용/닉네임 태그(`<`,`>`) 입력 차단,
  비밀번호 bcrypt 해시 + 존재하지 않는 계정에도 동일한 타이밍으로 응답(계정 존재 여부 추측 방지),
  모든 SQL은 PDO 준비된 구문만 사용, soft delete. 2026-08-16에 전체 코드베이스를 직접 감사해서
  회원가입에 빈도 제한이 빠져있던 것과 최소 작성 시간 검사를 우회할 수 있던 로직을 찾아 고쳤습니다.
- **성능**: CSS를 매 요청 HTML에 인라인 삽입해 페이지당 HTTP 요청 1개로 유지(저대역폭 회선 대응),
  gzip 압축, keyset 페이지네이션(대량 데이터에서도 빠름). 56Kbps 기준 실측: 각 페이지가
  1.2~2KB(gzip 후)로 압축되어 0.2~0.3초 안에 전송됩니다 — 아래 "성능 실측" 참고.

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
- **Security**: every output is escaped without exception (`View::e()`), CSRF tokens on every
  state-changing request, rate limiting on posting/login/registration (IP- and account-based),
  spam mitigation (honeypot + minimum fill time — a missing session marker fails the check
  instead of passing it), angle brackets (`<`, `>`) rejected in titles/bodies/nicknames, bcrypt
  password hashing with constant-time responses for nonexistent accounts (no timing oracle for
  username enumeration), every SQL query goes through PDO prepared statements, soft deletes.
  A full self-audit on 2026-08-16 found and fixed two real gaps: registration had no rate
  limiting at all, and the minimum-fill-time check could be silently bypassed by skipping the
  initial page load.
- **Performance**: CSS is inlined into every response (no separate stylesheet request — one HTTP
  request per page, which matters on high-latency connections), gzip compression, keyset pagination
  that stays fast at scale. Measured at simulated 56Kbps: every page compresses to 1.2-2KB and
  transfers in 0.2-0.3s — see "Measured performance" below.

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
