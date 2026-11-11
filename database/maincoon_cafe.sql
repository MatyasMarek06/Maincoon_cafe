-- =========================================================
-- Purrfect Maine – databáze maincoon_cafe
-- Import: phpMyAdmin -> vyberte databázi maincoon_cafe -> záložka SQL
--         -> vložte celý tento skript -> Proveď (Go)
--
-- Účet majitele po importu:  admin@maincoon.cz / admin123
-- =========================================================

SET NAMES utf8mb4;

-- Při opakovaném importu smaže staré tabulky (pozor – smaže i data!)
SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS reservations, products, cats, users;
SET FOREIGN_KEY_CHECKS = 1;
-- Uživatelé: zákazníci a majitel (role admin)
CREATE TABLE IF NOT EXISTS users (
  id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name          VARCHAR(100) NOT NULL,
  email         VARCHAR(190) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  role          ENUM('customer', 'admin') NOT NULL DEFAULT 'customer',
  created_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_czech_ci;

-- Kočky žijící v kavárně
CREATE TABLE IF NOT EXISTS cats (
  id                INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name              VARCHAR(60)  NOT NULL,
  nickname          VARCHAR(100) NOT NULL DEFAULT '',
  tag_label         VARCHAR(60)  NOT NULL DEFAULT '',      -- štítek na fotce, např. "👑 Šéf kavárny"
  age_years         DECIMAL(3,1) NOT NULL,
  sex               ENUM('kocour', 'kočka') NOT NULL,
  weight_kg         DECIMAL(4,1) NOT NULL,
  color             VARCHAR(80)  NOT NULL,
  personality       TEXT NOT NULL,
  favorite_activity TEXT NOT NULL,
  favorite_treat    VARCHAR(255) NOT NULL DEFAULT '',
  dislikes          VARCHAR(255) NOT NULL DEFAULT '',
  story             TEXT NULL,
  trait_cuddly      TINYINT UNSIGNED NOT NULL DEFAULT 5,   -- 0–10
  trait_playful     TINYINT UNSIGNED NOT NULL DEFAULT 5,
  trait_talkative   TINYINT UNSIGNED NOT NULL DEFAULT 5,
  trait_energy      TINYINT UNSIGNED NOT NULL DEFAULT 5,
  rest_days         VARCHAR(20)  NOT NULL DEFAULT '',      -- dny volna, např. "2,4" (0 = neděle)
  tags              VARCHAR(100) NOT NULL DEFAULT '',      -- filtry, např. "mazlivy klidny"
  highlights        VARCHAR(255) NOT NULL DEFAULT '',      -- zajímavosti oddělené čárkou
  photo             VARCHAR(500) NOT NULL DEFAULT '',      -- URL nebo cesta k nahranému souboru
  is_active         TINYINT(1) NOT NULL DEFAULT 1,
  sort_order        INT NOT NULL DEFAULT 0,
  created_at        DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_czech_ci;

-- Produkty v nabídce kavárny
CREATE TABLE IF NOT EXISTS products (
  id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  category     VARCHAR(60)  NOT NULL,
  name         VARCHAR(120) NOT NULL,
  description  VARCHAR(500) NOT NULL DEFAULT '',
  price        INT UNSIGNED NOT NULL,                      -- v Kč
  is_vegan     TINYINT(1) NOT NULL DEFAULT 0,
  is_available TINYINT(1) NOT NULL DEFAULT 1,
  sort_order   INT NOT NULL DEFAULT 0,
  created_at   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_czech_ci;

-- Rezervace zákazníků
CREATE TABLE IF NOT EXISTS reservations (
  id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  code       VARCHAR(12) NOT NULL UNIQUE,
  user_id    INT UNSIGNED NOT NULL,
  cat_id     INT UNSIGNED NULL,
  visit_date DATE NOT NULL,
  start_time TIME NOT NULL,
  duration   TINYINT UNSIGNED NOT NULL,                    -- v hodinách
  people     TINYINT UNSIGNED NOT NULL,
  note       VARCHAR(200) NOT NULL DEFAULT '',
  price      INT UNSIGNED NOT NULL,
  status     ENUM('confirmed', 'cancelled') NOT NULL DEFAULT 'confirmed',
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_visit (visit_date, status),
  CONSTRAINT fk_res_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  CONSTRAINT fk_res_cat  FOREIGN KEY (cat_id)  REFERENCES cats(id)  ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_czech_ci;

-- =========================================================
-- Ukázková data
-- =========================================================

INSERT INTO cats (name, nickname, tag_label, age_years, sex, weight_kg, color, personality, favorite_activity, favorite_treat, dislikes, story, trait_cuddly, trait_playful, trait_talkative, trait_energy, rest_days, tags, highlights, photo, sort_order) VALUES
('Thor', 'Velký zrzavý medvěd', '👑 Šéf kavárny', 5, 'kocour', 9.5, 'Červená mourovatá',
 'Typický mainský „něžný obr“ – klidný, sebevědomý a neuvěřitelně trpělivý. Navzdory 9,5 kg je jemný jako plyšák a ke každému hostu přistupuje s důstojnou zvědavostí.',
 'Dohlížení na provoz z nejvyšší poličky nad barem – jako správný mainský mýval miluje výšky a rozhled. Odpoledne spí natažený přes celý parapet.',
 'Sušené kuřecí kousky – za ně udělá i „panáčka“.',
 'Vysavač a když mu někdo sedí na jeho křesle u krbu.',
 'Thor má všechno, co si člověk pod mainskou mývalí kočkou představí: rysí štětičky na uších, mohutnou lví hřívu a ocas skoro tak dlouhý jako tělo. I s ocasem měří přes metr a noví hosté ho často zaměňují za malého rysa.',
 9, 5, 6, 4, '1', 'mazlivy klidny deti', 'Rysí štětičky, Přes metr dlouhý, Kamarád dětí',
 'images/cats/thor.jpg', 1),

('Freya', 'Modrá bouřka', '⚡ Akrobatka', 3, 'kočka', 6.2, 'Modrá',
 'Chytrá a zvídavá jako snad každá mainská kočka – umí otevřít šuplík i dvířka skříňky, proto máme dětské pojistky. S lidmi „mluví“ typickým tichým cvrlikáním místo mňoukání.',
 'Lov peříčkové udice, šplhání na kočičí strom až ke stropu a aportování. Mainské kočky bývají hravé skoro jako psi a Freya je toho důkazem.',
 'Lyofilizovaný losos.',
 'Zavřené dveře – musí vědět, co se děje za nimi.',
 'Freya má velké uši s dlouhými štětičkami a hebkou modrošedou srst. Když si chce hrát, přinese vám hračku v tlamě a položí ji k nohám.',
 5, 10, 8, 9, '3', 'hravy deti', 'Aportuje hračky, Cvrliká místo mňoukání, Ideální pro hravé děti',
 'images/cats/freya.jpg', 2),

('Oskar', 'Profesor', '🧘 Moudrý senior', 8, 'kocour', 8.7, 'Hnědá mourovatá s bílou',
 'Rozvážný gentleman se širokou hrudí a hřívou, za kterou by se nemusel stydět ani lev. Lidi si vybírá pečlivě, ale jakmile si vás oblíbí, stráví celé odpoledne vedle vás.',
 'Ležení u hostů, kteří čtou knihu, a pozorování ptáků z okna. Mainští mývalové byli původně lovci myší na farmách v Maine a Oskar si ten instinkt pořád pamatuje.',
 'Kousek vařené krůty (jen od personálu!).',
 'Hlasité zvuky a rychlé pohyby – v jeho přítomnosti prosíme o klid.',
 'Oskar má klasickou hnědou mourovatou kresbu, nejtypičtější zbarvení plemene, které připomíná původní farmářské kočky z Nové Anglie. K nám přišel z chovatelské stanice do „chovatelského důchodu“ a v knihovně má svůj vlastní polštář.',
 10, 2, 3, 2, '2,4', 'mazlivy klidny', 'Klíňák, Klasická kresba plemene, Pro klidné návštěvy',
 'images/cats/oskar.jpg', 3),

('Luna', 'Stín', '🌙 Noční princezna', 2, 'kočka', 5.8, 'Černá kouřová',
 'Něžná a na začátku trochu ostýchavá, pak ale neodbytná mazlinka. Ráda si „povídá“ trylkovitým hlasem, typickým pro mainské kočky.',
 'Hra s vodou v kočičí fontánce – namáčí do ní tlapky a pak je pečlivě olizuje. Láska k vodě je pro mainské mývaly typická, jejich hustá srst téměř nepromokne.',
 'Pasta v tubě s tuňákem.',
 'Když ji někdo zvedá do náruče – raději přijde sama.',
 'Luna má kouřovou srst: každý chlup je u kořínku stříbřitý a na konci černý, takže se srst při pohybu třpytí. Hosté ji nejčastěji fotí, jak u fontánky zkoumá kapky s vědeckým zaujetím.',
 8, 7, 9, 6, '5', 'mazlivy hravy deti', 'Milovnice vody, Kouřová srst, Cvrlikající konverzátorka',
 'images/cats/luna.jpg', 4),

('Medvídek', 'Plyšák', '🍼 Nejmladší', 1.5, 'kocour', 6.9, 'Krémová s bílou',
 'Bezstarostný puberťák s nekonečnou zásobou energie a obrovskými tlapami, do kterých teprve dorůstá. Každého hosta považuje za nového nejlepšího kamaráda.',
 'Schovávání v papírových krabicích, lovení tkaniček od bot a přepadávání Thora ze zálohy.',
 'Cokoli, co zrovna jí někdo jiný.',
 'Nudu. A když hra skončí dřív, než on chce.',
 'Mainské mývalí kočky dospívají pomalu – naplno vyrostou až kolem 4 let. Medvídek tak bude ještě pár let růst a podle veterináře by jednou mohl překonat i Thora.',
 7, 10, 5, 10, '', 'hravy deti', 'Krabicový expert, Velké tlapy, Nejlepší kamarád dětí',
 'images/cats/medvidek.jpg', 5);

INSERT INTO products (category, name, description, price, is_vegan, sort_order) VALUES
('Káva', 'Espresso', 'Výběrová káva z české pražírny, aktuálně Etiopie Guji.', 55, 1, 1),
('Káva', 'Cappuccino', 'Espresso s jemně napěněným mlékem z rodinné farmy.', 75, 0, 2),
('Káva', 'Flat white', 'Dvojité ristretto a sametové mléko.', 85, 0, 3),
('Káva', 'Mainské latte', 'Naše specialita s pravým javorovým sirupem – pocta státu Maine.', 95, 0, 4),
('Káva', 'Filtr dne', 'Ručně překapávaná káva, odrůda se mění každý týden.', 70, 1, 5),
('Čaj a nápoje', 'Matcha latte', 'Japonská matcha s ovesným nebo kravským mlékem.', 95, 1, 1),
('Čaj a nápoje', 'Horká čokoláda', 'Belgická čokoláda 54 %, šlehačka na přání.', 85, 0, 2),
('Čaj a nápoje', 'Domácí limonáda', 'Bezinka s citronem nebo malina s mátou.', 75, 1, 3),
('Čaj a nápoje', 'Sypaný čaj', 'Výběr z 12 druhů čajů, konvička 0,5 l.', 80, 1, 4),
('Dezerty', 'Borůvkový cheesecake', 'Pečený cheesecake s borůvkami – další pocta Maine.', 105, 0, 1),
('Dezerty', 'Mrkvový dort', 'Vláčný dort s vlašskými ořechy a krémem z mascarpone.', 95, 0, 2),
('Dezerty', 'Veganské brownie', 'Hořká čokoláda, lískové ořechy, bez živočišných surovin.', 80, 1, 3),
('Dezerty', 'Kočičí tlapky', 'Máslové sušenky ve tvaru tlapek, 3 ks.', 45, 0, 4),
('Pro kočky', 'Pochoutka pro naše kočky', 'Prémiový pamlsek, který kočce podá personál ve vaší přítomnosti.', 40, 0, 1);

-- Účet majitele (heslo: admin123)
INSERT INTO users (name, email, password_hash, role) VALUES
('Majitel', 'admin@maincoon.cz', '$2y$10$hs2awQqLKLy.p.QPXXOQrODGUS2Vwx8qZSNnt9l85lppE2Jfh97Ii', 'admin');