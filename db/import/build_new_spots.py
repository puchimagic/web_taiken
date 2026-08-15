#!/usr/bin/env python3
"""
50件の新規ニッチスポットのシードデータを db/seed_spots_new.json に書き出す。
実行方法: python3 db/import/build_new_spots.py
"""
import json
from pathlib import Path

ROOT = Path(__file__).resolve().parent.parent.parent
OUT_PATH = ROOT / "db" / "seed_spots_new.json"

# (title, address, lat, lon, tags, commons_query, file)
SPOTS = [
    ("タウシュベツ川橋梁・幻のコンクリートアーチ橋", "北海道河東郡上士幌町", 43.2244, 143.2703,
     "鉄道 廃線 秘境 眼鏡橋", "Taushubetsu River Bridge", "taushubetsu_bridge.jpg"),
    ("小幌駅・列車でしか行けない秘境駅", "北海道虻田郡豊浦町", 42.5306, 140.7972,
     "鉄道 秘境駅 無人駅", "Hoboro Station Hokkaido", "hoboro_station.jpg"),
    ("姨捨駅・日本三大車窓のスイッチバック", "長野県千曲市", 36.5375, 138.1017,
     "鉄道 棚田 絶景 スイッチバック", "Obasute Station Nagano", "obasute_station.jpg"),
    ("京都鉄道博物館の扇形車庫", "京都府京都市下京区", 34.9856, 135.7397,
     "鉄道 博物館 蒸気機関車", "Kyoto Railway Museum roundhouse", "kyoto_railway_museum.jpg"),
    ("余部橋梁・空を渡る鉄道橋", "兵庫県美方郡香美町", 35.6389, 134.8253,
     "鉄道 橋梁 絶景", "Amarube Viaduct", "amarube_viaduct.jpg"),
    ("黒部峡谷トロッコ電車の絶景区間", "富山県黒部市", 36.8814, 137.5556,
     "鉄道 渓谷 絶景", "Kurobe Gorge Railway", "kurobe_gorge_railway.jpg"),
    ("大井川鐵道を走るSL列車", "静岡県島田市", 34.8228, 138.1364,
     "鉄道 SL レトロ", "Oigawa Railway steam locomotive", "oigawa_sl.jpg"),
    ("十勝三股・幻の終着駅跡", "北海道上川郡新得町", 43.3350, 142.9469,
     "鉄道 廃線 秘境", "Tokachi Mitsumata former station", "tokachi_mitsumata.jpg"),
    ("尾道本通り商店街・映画の舞台になった坂道の町", "広島県尾道市", 34.4083, 133.2044,
     "聖地巡礼 商店街 レトロ 映画", "Onomichi Hondori shopping street", "onomichi_hondori.jpg"),
    ("鞆の浦・ジブリ作品ゆかりの港町", "広島県福山市", 34.3814, 133.3814,
     "聖地巡礼 港町 レトロ", "Tomonoura port town", "tomonoura.jpg"),
    ("江の島・湘南アニメ聖地の定番スポット", "神奈川県藤沢市", 35.2989, 139.4808,
     "聖地巡礼 島 絶景", "Enoshima island", "enoshima.jpg"),
    ("道後温泉本館・千と千尋の元ネタとされる名湯", "愛媛県松山市", 33.8517, 132.7856,
     "聖地巡礼 温泉 レトロ建築", "Dogo Onsen Honkan", "dogo_onsen.jpg"),
    ("秩父・アニメロケ地巡りで人気の街並み", "埼玉県秩父市", 35.9917, 139.0783,
     "聖地巡礼 街並み 神社", "Chichibu shrine townscape", "chichibu.jpg"),
    ("鎌倉高校前踏切・バスケ漫画の聖地", "神奈川県鎌倉市", 35.3072, 139.5308,
     "聖地巡礼 踏切 海", "Kamakurakoukoumae Station crossing", "kamakurakoukoumae.jpg"),
    ("官営八幡製鐵所旧本事務所・世界遺産の産業遺構", "福岡県北九州市", 33.8867, 130.8206,
     "産業遺産 世界遺産 レンガ建築", "Yawata Steel Works old headquarters", "yawata_steel_works.jpg"),
    ("別子銅山跡・東平の産業遺産群", "愛媛県新居浜市", 33.9339, 133.2467,
     "産業遺産 廃墟 鉱山", "Besshi Copper Mine Toneken", "besshi_copper_mine.jpg"),
    ("四日市コンビナート・幻想的な工場夜景", "三重県四日市市", 34.9650, 136.6244,
     "工場夜景 産業遺産 夜景", "Yokkaichi kombinato night view", "yokkaichi_kombinato.jpg"),
    ("三菱重工業長崎造船所・占勝閣の眺め", "長崎県長崎市", 32.7386, 129.8681,
     "産業遺産 造船 工場夜景", "Mitsubishi Nagasaki Shipyard", "nagasaki_shipyard.jpg"),
    ("池島・かつての炭鉱の島", "長崎県長崎市", 32.8851, 129.6002,
     "産業遺産 廃墟 島", "Ikeshima coal mine island", "ikeshima.jpg"),
    ("室蘭・白鳥大橋展望台からの工場夜景", "北海道室蘭市", 42.3442, 140.9499,
     "工場夜景 橋 夜景", "Muroran Hakucho Bridge night view", "muroran_hakucho_bridge.jpg"),
    ("軍艦島（端島）・廃墟の無人島", "長崎県長崎市", 32.6272, 129.7386,
     "廃墟 産業遺産 世界遺産 島", "Hashima Island Gunkanjima", "hashima_island.jpg"),
    ("猿島・旧陸軍要塞のレンガトンネル", "神奈川県横須賀市", 35.2850, 139.6932,
     "廃墟 軍事遺構 島 レンガ", "Sarushima Fort ruins", "sarushima.jpg"),
    ("松代大本営地下壕・戦争遺構", "長野県長野市", 36.5522, 138.1817,
     "廃墟 軍事遺構 地下壕", "Matsushiro Underground Imperial Headquarters", "matsushiro_bunker.jpg"),
    ("舞鶴赤れんがパーク・旧海軍倉庫群", "京都府舞鶴市", 35.4748, 135.3814,
     "産業遺産 レンガ建築 軍事遺構", "Maizuru Red Brick Park", "maizuru_red_brick.jpg"),
    ("太刀洗平和記念館・特攻基地の記憶", "福岡県朝倉郡筑前町", 33.4128, 130.6194,
     "軍事遺構 資料館 平和学習", "Tachiarai Peace Memorial Museum", "tachiarai_peace_museum.jpg"),
    ("野辺山宇宙電波観測所・巨大パラボラアンテナ", "長野県南佐久郡南牧村", 35.9425, 138.4728,
     "天文 電波望遠鏡 絶景", "Nobeyama Radio Observatory", "nobeyama_observatory.jpg"),
    ("種子島宇宙センター・ロケット打ち上げの聖地", "鹿児島県熊毛郡南種子町", 30.3900, 130.9686,
     "宇宙 ロケット 岬", "Tanegashima Space Center", "tanegashima_space_center.jpg"),
    ("国立天文台三鷹キャンパス・歴史的観測ドーム", "東京都三鷹市", 35.6742, 139.5386,
     "天文 観測所 歴史的建築", "National Astronomical Observatory of Japan Mitaka", "mitaka_observatory.jpg"),
    ("美星天文台・日本一の星空指定地", "岡山県井原市", 34.5789, 133.5322,
     "天文 星空 観測所", "Bisei Astronomical Observatory", "bisei_observatory.jpg"),
    ("常盤平団地・昭和レトロ団地の代表格", "千葉県松戸市", 35.7875, 139.9231,
     "団地 レトロ建築 昭和", "Tokiwadaira danchi Matsudo", "tokiwadaira_danchi.jpg"),
    ("香川県庁舎旧本館・モダニズム建築の傑作", "香川県高松市", 34.3428, 134.0453,
     "レトロ建築 モダニズム 庁舎", "Kagawa Prefectural Government Hall old building", "kagawa_kencho.jpg"),
    ("代官山・同潤会アパート跡地の街並み", "東京都渋谷区", 35.6497, 139.7031,
     "レトロ建築 団地 街並み", "Daikanyama Address Tokyo", "daikanyama.jpg"),
    ("高蔵寺ニュータウン・昭和の団地遺産", "愛知県春日井市", 35.2497, 137.0044,
     "団地 昭和 ニュータウン", "Kozoji New Town Kasugai", "kozoji_newtown.jpg"),
    ("牛久大仏・世界最大級のブロンズ立像", "茨城県牛久市", 35.9694, 140.1428,
     "珍スポット 巨大仏像", "Ushiku Daibutsu", "ushiku_daibutsu.jpg"),
    ("五色沼・摩訶不思議な色をたたえる沼群", "福島県耶麻郡北塩原村", 37.6572, 140.0964,
     "自然 湖沼 絶景", "Goshikinuma Five Colored Ponds", "goshikinuma.jpg"),
    ("尾道・猫がたたずむ細い路地裏", "広島県尾道市", 34.4092, 133.2028,
     "珍スポット 路地裏 猫", "Onomichi Cat Alley", "onomichi_cat_alley.jpg"),
    ("満濃池・日本最大の灌漑用ため池", "香川県仲多度郡まんのう町", 34.1381, 133.8494,
     "自然 ため池 歴史", "Manno Pond Kagawa", "manno_pond.jpg"),
    ("マザー牧場・房総の高原にそびえる観覧車", "千葉県富津市", 35.2094, 139.9219,
     "珍スポット 牧場 観覧車", "Mother Farm Chiba", "mother_farm.jpg"),
    ("大久野島・うさぎと廃墟の島", "広島県竹原市", 34.3086, 132.9689,
     "廃墟 島 珍スポット", "Okunoshima Rabbit Island poison gas factory", "okunoshima.jpg"),
    ("原鉄道模型博物館・精密な鉄道模型の世界", "神奈川県横浜市西区", 35.4589, 139.6297,
     "鉄道 博物館 模型", "Hara Model Railway Museum", "hara_railway_museum.jpg"),
    ("大牟田市石炭産業科学館・炭鉱の記憶", "福岡県大牟田市", 33.0310, 130.4245,
     "産業遺産 博物館 炭鉱", "Omuta Coal Industry Science Museum", "omuta_coal_museum.jpg"),
    ("竹中大工道具館・伝統技術の博物館", "兵庫県神戸市中央区", 34.7031, 135.1928,
     "博物館 伝統工芸 建築", "Takenaka Carpentry Tools Museum", "takenaka_carpentry_museum.jpg"),
    ("博物館明治村・移築された近代建築群", "愛知県犬山市", 35.3161, 136.9414,
     "博物館 レトロ建築 野外博物館", "Meiji Mura open air museum", "meiji_mura.jpg"),
    ("秋芳洞・日本屈指の大鍾乳洞", "山口県美祢市", 34.2306, 131.3086,
     "自然 鍾乳洞 地質", "Akiyoshido Cave", "akiyoshido.jpg"),
    ("大室山・お椀型の美しい火山地形", "静岡県伊東市", 34.9147, 139.0906,
     "自然 火山 地質", "Mount Omuro Izu", "mount_omuro.jpg"),
    ("玄武洞・柱状節理が織りなす洞窟", "兵庫県豊岡市", 35.5361, 134.8153,
     "自然 地質 洞窟", "Genbudo Cave basalt", "genbudo.jpg"),
    ("昇仙峡・花崗岩がそびえる奇岩渓谷", "山梨県甲府市", 35.6733, 138.6444,
     "自然 渓谷 奇岩", "Shosenkyo Gorge", "shosenkyo.jpg"),
    ("丸亀城・現存12天守と高石垣", "香川県丸亀市", 34.2894, 133.7972,
     "城 現存天守 石垣", "Marugame Castle", "marugame_castle.jpg"),
    ("備中松山城・雲海に浮かぶ天空の城", "岡山県高梁市", 34.7683, 133.6178,
     "城 現存天守 雲海", "Bitchu Matsuyama Castle", "bitchu_matsuyama_castle.jpg"),
    ("弘前城・桜と現存天守の競演", "青森県弘前市", 40.6083, 140.4633,
     "城 現存天守 桜", "Hirosaki Castle", "hirosaki_castle.jpg"),
]


def main():
    spots = []
    for title, address, lat, lon, tags, query, file_name in SPOTS:
        spots.append({
            "title": title,
            "address": address,
            "lat": lat,
            "lon": lon,
            "tags": tags,
            "commons_query": query,
            "file": file_name,
        })

    OUT_PATH.write_text(
        json.dumps(spots, ensure_ascii=False, indent=2) + "\n",
        encoding="utf-8",
    )
    print(f"{len(spots)}件を {OUT_PATH} に書き出しました")


if __name__ == "__main__":
    main()
