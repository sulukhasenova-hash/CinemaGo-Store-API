<?php
declare(strict_types=1);
$config=require __DIR__.'/config.php';
$dir=dirname($config['database']); if(!is_dir($dir)) mkdir($dir,0775,true);
if(file_exists($config['database'])) { echo "База уже существует. Для пересоздания удалите api/storage/cinemago.sqlite вручную.\n"; exit; }
$pdo=new PDO('sqlite:'.$config['database']);
$pdo->setAttribute(PDO::ATTR_ERRMODE,PDO::ERRMODE_EXCEPTION);
$pdo->exec('PRAGMA foreign_keys=ON');
$schema=<<<SQL
CREATE TABLE categories(id INTEGER PRIMARY KEY,name TEXT NOT NULL);
CREATE TABLE subcategories(id INTEGER PRIMARY KEY,category_id INTEGER NOT NULL REFERENCES categories(id),name TEXT NOT NULL);
CREATE TABLE customer_groups(id INTEGER PRIMARY KEY,name TEXT NOT NULL,description TEXT NOT NULL);
CREATE TABLE preferences(id INTEGER PRIMARY KEY,code TEXT UNIQUE NOT NULL,name TEXT NOT NULL);
CREATE TABLE users(id INTEGER PRIMARY KEY,login TEXT UNIQUE NOT NULL,display_name TEXT NOT NULL,password_hash TEXT NOT NULL,group_id INTEGER NOT NULL REFERENCES customer_groups(id));
CREATE TABLE products(id INTEGER PRIMARY KEY,name TEXT NOT NULL,description TEXT NOT NULL,price REAL NOT NULL,stock INTEGER NOT NULL,product_type TEXT NOT NULL,category_id INTEGER NOT NULL REFERENCES categories(id),subcategory_id INTEGER NOT NULL REFERENCES subcategories(id),image TEXT NOT NULL,is_active INTEGER NOT NULL DEFAULT 1);
CREATE TABLE user_preferences(user_id INTEGER NOT NULL REFERENCES users(id),preference_id INTEGER NOT NULL REFERENCES preferences(id),weight REAL NOT NULL CHECK(weight BETWEEN -5 AND 5),PRIMARY KEY(user_id,preference_id));
CREATE TABLE group_preferences(group_id INTEGER NOT NULL REFERENCES customer_groups(id),preference_id INTEGER NOT NULL REFERENCES preferences(id),weight REAL NOT NULL,PRIMARY KEY(group_id,preference_id));
CREATE TABLE group_categories(group_id INTEGER NOT NULL REFERENCES customer_groups(id),category_id INTEGER NOT NULL REFERENCES categories(id),weight REAL NOT NULL,PRIMARY KEY(group_id,category_id));
CREATE TABLE product_preferences(product_id INTEGER NOT NULL REFERENCES products(id),preference_id INTEGER NOT NULL REFERENCES preferences(id),weight REAL NOT NULL,PRIMARY KEY(product_id,preference_id));
CREATE TABLE product_similarity(product_id INTEGER NOT NULL REFERENCES products(id),related_product_id INTEGER NOT NULL REFERENCES products(id),weight REAL NOT NULL,PRIMARY KEY(product_id,related_product_id));
CREATE TABLE events(id INTEGER PRIMARY KEY AUTOINCREMENT,event_id TEXT UNIQUE NOT NULL,user_id INTEGER NOT NULL REFERENCES users(id),product_id INTEGER NOT NULL REFERENCES products(id),event_type TEXT NOT NULL,created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP);
SQL;
$pdo->exec($schema);
$categories=['Фильмы на Blu-ray','Фильмы на DVD','Билеты в кино','Коллекционные издания','Саундтреки','Аксессуары','Постеры и арт','Подарочные сертификаты'];
$subs=['Новинки','Классика','Хоррор'];
foreach($categories as $i=>$name){$pdo->prepare('INSERT INTO categories VALUES(?,?)')->execute([$i+1,$name]);foreach($subs as $j=>$sn)$pdo->prepare('INSERT INTO subcategories VALUES(?,?,?)')->execute([$i*3+$j+1,$i+1,$sn]);}
$groups=['Новые','Постоянные','Киноманы','Коллекционеры'];
foreach($groups as $i=>$g)$pdo->prepare('INSERT INTO customer_groups VALUES(?,?,?)')->execute([$i+1,$g,'Учебная группа покупателей: '.$g]);
$prefs=['action'=>'Боевики','drama'=>'Драмы','horror'=>'Ужасы','comedy'=>'Комедии','scifi'=>'Фантастика','thriller'=>'Триллеры','romance'=>'Романтика','animation'=>'Анимация','kazakh'=>'Казахстанское кино','classic'=>'Классика','collector'=>'Коллекционные товары','music'=>'Музыка и саундтреки'];
$pid=1;foreach($prefs as $code=>$name)$pdo->prepare('INSERT INTO preferences VALUES(?,?,?)')->execute([$pid++,$code,$name]);
$hash=password_hash('cinema123',PASSWORD_DEFAULT);
for($i=1;$i<=10;$i++)$pdo->prepare('INSERT INTO users VALUES(?,?,?,?,?)')->execute([$i,'user'.$i,'Покупатель '.$i,$hash,(($i-1)%4)+1]);
$pdo->beginTransaction();
$types=['Blu-ray диск','DVD-диск','Билет','Коллекционное издание','Саундтрек','Аксессуар','Постер','Сертификат'];
$titles=['Интерстеллар','Начало','Дюна','Матрица','Оно','Заклятие','Рэкетир','Рэкетир 2','Дәстүр','Дәстүр: Теріс бата','Шум','Паразиты','Оппенгеймер','Бегущий по лезвию 2049','Человек-паук: Через вселенные','Унесённые призраками','Гладиатор','Джокер','Тихое место','Астрал','Коко','Ла-Ла Ленд','Пятый элемент','Титаник','Криминальное чтиво'];
$descs=['Качественное издание для домашней коллекции.','Отличный выбор для вечера кино.','Подарочный вариант для поклонников фильма.','Коллекционный товар с тематическим оформлением.'];
for($i=1;$i<=200;$i++){
  $cat=(($i-1)%8)+1; $sub=(($cat-1)*3)+((int)(($i-1)/8)%3)+1; $type=$types[$cat-1];
  $title=$titles[($i-1)%count($titles)];
  $name=$type.' — '.$title.((int)(($i-1)/count($titles))>0?' #'.(int)(($i-1)/count($titles)+1):'');
  $price=($cat===3?2500:($cat===8?5000:3500)) + (($i*137)%8500);
  $stock=1+(($i*7)%30);
  $pdo->prepare('INSERT INTO products(id,name,description,price,stock,product_type,category_id,subcategory_id,image,is_active) VALUES(?,?,?,?,?,?,?,?,?,1)')
      ->execute([$i,$name,$descs[$i%4],$price,$stock,$type,$cat,$sub,'https://placehold.co/480x640/171923/e8e8ee?text='.rawurlencode('CinemaGo+'.$i)]);
  for($p=1;$p<=12;$p++){ $w=((($i*3+$p*7)%11)-5)/5; if(abs($w)>0.05)$pdo->prepare('INSERT INTO product_preferences VALUES(?,?,?)')->execute([$i,$p,$w]); }
}
for($g=1;$g<=4;$g++){
 for($p=1;$p<=12;$p++)$pdo->prepare('INSERT INTO group_preferences VALUES(?,?,?)')->execute([$g,$p,((($g*5+$p*3)%9)-4)/4]);
 for($c=1;$c<=8;$c++)$pdo->prepare('INSERT INTO group_categories VALUES(?,?,?)')->execute([$g,$c,((($g*7+$c*2)%9)-4)/4]);
}
for($u=1;$u<=10;$u++)for($p=1;$p<=12;$p++)$pdo->prepare('INSERT INTO user_preferences VALUES(?,?,?)')->execute([$u,$p,((($u*3+$p*5)%11)-5)/5]);
for($i=1;$i<=200;$i++)for($k=1;$k<=3;$k++){ $rel=(($i+$k*7-1)%200)+1;if($rel!==$i)$pdo->prepare('INSERT OR IGNORE INTO product_similarity VALUES(?,?,?)')->execute([$i,$rel,1-($k*0.18)]); }
$pdo->commit();
echo "Готово: создана база CinemaGo с 200 товарами, 8 категориями, 24 подкатегориями, 10 аккаунтами, 4 группами, 12 предпочтениями и пятью матрицами.\nПароль всех аккаунтов: cinema123\n";
