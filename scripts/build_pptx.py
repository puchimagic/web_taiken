# -*- coding: utf-8 -*-
from pptx import Presentation
from pptx.util import Inches, Pt, Emu
from pptx.dml.color import RGBColor
from pptx.enum.text import PP_ALIGN, MSO_ANCHOR
from pptx.enum.shapes import MSO_SHAPE
from pptx.oxml.ns import qn
import copy

# ---- カラーパレット（シンプル・落ち着いたブルー系） ----
NAVY = RGBColor(0x1B, 0x2A, 0x4A)      # 見出し・濃色
BLUE = RGBColor(0x2F, 0x5D, 0xD3)      # アクセント
BLUE_LIGHT = RGBColor(0xEA, 0xF0, 0xFD)  # 薄い背景
GRAY = RGBColor(0x5A, 0x63, 0x73)      # 本文グレー
WHITE = RGBColor(0xFF, 0xFF, 0xFF)
BG = RGBColor(0xFB, 0xFC, 0xFE)
LINE = RGBColor(0xDD, 0xE3, 0xEE)

FONT = "Yu Gothic"

prs = Presentation()
prs.slide_width = Inches(13.333)
prs.slide_height = Inches(7.5)
SW, SH = prs.slide_width, prs.slide_height
blank = prs.slide_layouts[6]


def add_slide():
    s = prs.slides.add_slide(blank)
    bg = s.shapes.add_shape(MSO_SHAPE.RECTANGLE, 0, 0, SW, SH)
    bg.fill.solid()
    bg.fill.fore_color.rgb = BG
    bg.line.fill.background()
    bg.shadow.inherit = False
    s.shapes._spTree.remove(bg._element)
    s.shapes._spTree.insert(2, bg._element)
    return s


def set_font(run, size, color=NAVY, bold=False, font=FONT):
    run.font.size = Pt(size)
    run.font.color.rgb = color
    run.font.bold = bold
    run.font.name = font
    rPr = run._r.get_or_add_rPr()
    ea = rPr.makeelement(qn('a:ea'), {'typeface': font})
    rPr.append(ea)


def add_text(slide, left, top, width, height, text, size=18, color=NAVY,
             bold=False, align=PP_ALIGN.LEFT, anchor=MSO_ANCHOR.TOP, line_spacing=1.15,
             font=FONT):
    tb = slide.shapes.add_textbox(left, top, width, height)
    tf = tb.text_frame
    tf.word_wrap = True
    tf.vertical_anchor = anchor
    lines = text.split("\n")
    for i, line in enumerate(lines):
        p = tf.paragraphs[0] if i == 0 else tf.add_paragraph()
        p.alignment = align
        p.line_spacing = line_spacing
        r = p.add_run()
        r.text = line
        set_font(r, size, color, bold, font)
    return tb


def add_bullets(slide, left, top, width, height, items, size=15, color=GRAY,
                 bullet_color=BLUE, line_spacing=1.25, space_after=8, font=FONT):
    """items: list of (level, text) ; level 0 = main bullet, 1 = sub bullet"""
    tb = slide.shapes.add_textbox(left, top, width, height)
    tf = tb.text_frame
    tf.word_wrap = True
    for i, (level, text) in enumerate(items):
        p = tf.paragraphs[0] if i == 0 else tf.add_paragraph()
        p.line_spacing = line_spacing
        p.space_after = Pt(space_after)
        mark = "●" if level == 0 else "–"
        indent = "" if level == 0 else "    "
        r = p.add_run()
        r.text = f"{indent}{mark}  {text}"
        set_font(r, size - (2 if level == 1 else 0), color if level == 0 else RGBColor(0x74, 0x7E, 0x8F), bold=False, font=font)
    return tb


def add_rect(slide, left, top, width, height, fill=None, line=None, line_width=None, radius=None):
    shape_type = MSO_SHAPE.ROUNDED_RECTANGLE if radius else MSO_SHAPE.RECTANGLE
    shp = slide.shapes.add_shape(shape_type, left, top, width, height)
    if radius:
        try:
            shp.adjustments[0] = radius
        except Exception:
            pass
    if fill is None:
        shp.fill.background()
    else:
        shp.fill.solid()
        shp.fill.fore_color.rgb = fill
    if line is None:
        shp.line.fill.background()
    else:
        shp.line.color.rgb = line
        shp.line.width = line_width or Pt(1)
    shp.shadow.inherit = False
    return shp


def add_header(slide, kicker, title, number=None, total=None):
    # kicker
    add_text(slide, Inches(0.7), Inches(0.42), Inches(8), Inches(0.4),
              kicker, size=13, color=BLUE, bold=True)
    # title
    add_text(slide, Inches(0.7), Inches(0.75), Inches(10.5), Inches(0.9),
              title, size=30, color=NAVY, bold=True)
    # underline accent
    add_rect(slide, Inches(0.7), Inches(1.42), Inches(0.6), Pt(4), fill=BLUE)
    if number is not None:
        add_text(slide, Inches(12.2), Inches(0.42), Inches(0.9), Inches(0.4),
                  f"{number:02d}", size=13, color=RGBColor(0xAE, 0xB8, 0xC8), align=PP_ALIGN.RIGHT)


def add_footer(slide, page, total):
    add_text(slide, Inches(0.7), Inches(7.08), Inches(6), Inches(0.35),
              "Webプログラミング体験授業", size=9, color=RGBColor(0xAE, 0xB8, 0xC8))
    add_text(slide, Inches(11.9), Inches(7.08), Inches(0.8), Inches(0.35),
              f"{page} / {total}", size=9, color=RGBColor(0xAE, 0xB8, 0xC8), align=PP_ALIGN.RIGHT)


TOTAL = 12
page_counter = {"n": 0}


def next_page():
    page_counter["n"] += 1
    return page_counter["n"]


# =========================================================
# Slide 1: 表紙
# =========================================================
s = add_slide()
add_rect(s, 0, 0, SW, Inches(7.5), fill=NAVY)
add_rect(s, 0, Inches(6.9), SW, Inches(0.12), fill=BLUE)
add_text(s, Inches(0.9), Inches(2.5), Inches(10), Inches(0.5),
          "Webプログラミング体験授業", size=20, color=RGBColor(0x9F, 0xB4, 0xE8), bold=True)
add_text(s, Inches(0.85), Inches(3.0), Inches(11), Inches(1.6),
          "動画共有サイトを\n完成させてみよう", size=44, color=WHITE, bold=True, line_spacing=1.15)
add_rect(s, Inches(0.9), Inches(4.55), Inches(0.7), Pt(5), fill=BLUE)
add_text(s, Inches(0.9), Inches(4.85), Inches(10), Inches(0.5),
          "コードを直して、動きが変わる体験をしよう", size=16, color=RGBColor(0xC7, 0xD2, 0xEE))
next_page()

# =========================================================
# Slide 2: この体験授業で伝えたいこと
# =========================================================
s = add_slide()
p = next_page()
add_header(s, "INTRODUCTION", "この体験授業で伝えたいこと", p)

add_rect(s, Inches(0.7), Inches(1.75), Inches(11.9), Inches(1.5), fill=BLUE_LIGHT, radius=0.08)
add_text(s, Inches(1.0), Inches(1.95), Inches(11.3), Inches(1.15),
          "「動画共有サイト」の未完成な部分を少しずつ完成させながら、\n"
          "“コードを直すとWebアプリの動きが変わる”ことを手を動かして体感する。",
          size=17, color=NAVY, bold=True, line_spacing=1.4)

add_text(s, Inches(0.7), Inches(3.55), Inches(6), Inches(0.4), "大事にしたいこと", size=15, color=BLUE, bold=True)
add_bullets(s, Inches(0.7), Inches(4.0), Inches(5.6), Inches(2.6), [
    (0, "表側（フロントエンド）だけでなく、"),
    (1, "裏側（バックエンド）の存在と役割を理解する"),
    (0, "料理・キッチンの比喩を交えてイメージしやすく"),
    (0, "タイピングは最小限、既存コードを直す体験を中心に"),
], size=15)

add_text(s, Inches(6.7), Inches(3.55), Inches(6), Inches(0.4), "体験できる項目（全6項目）", size=15, color=BLUE, bold=True)
items = ["0. 導入：プログラミングって何？", "1. コードと動作の因果関係", "2. 関数とは何か",
         "3. データベースとは何か", "4. 入力チェック（バリデーション）", "5〜6. アレンジ課題・準備のしやすさ"]
top = Inches(4.0)
for i, t in enumerate(items):
    row_top = Emu(int(top) + int(Inches(0.42)) * i)
    add_rect(s, Inches(6.7), row_top, Inches(5.9), Inches(0.36), fill=WHITE, line=LINE, line_width=Pt(0.75), radius=0.2)
    add_text(s, Inches(6.95), Emu(int(row_top) + int(Inches(0.02))), Inches(5.4), Inches(0.32), t, size=13, color=GRAY)

add_footer(s, p, TOTAL)

# =========================================================
# Slide 3: 導入 プログラミングって何？
# =========================================================
s = add_slide()
p = next_page()
add_header(s, "STEP 0", "導入：プログラミングって何？", p)

add_text(s, Inches(0.7), Inches(1.7), Inches(11.9), Inches(0.4),
          "情報システムを作る流れは「家を建てる」「料理を作る」ことに例えられる", size=15, color=GRAY)

# table-like 3 columns
cols = [
    ("要件定義", "お客さんの要望を聞く"),
    ("設計", "設計図・レシピを作る"),
    ("実装", "建築・調理する"),
    ("テスト", "検査・味見する"),
    ("運用保守", "入居・食事する"),
]
col_w = Inches(2.24)
gap = Inches(0.1)
x0 = Inches(0.7)
y0 = Inches(2.25)
for i, (label, desc) in enumerate(cols):
    x = Emu(int(x0) + i * (int(col_w) + int(gap)))
    highlight = label in ("実装", "テスト")
    fill = BLUE if highlight else WHITE
    txtcolor = WHITE if highlight else NAVY
    add_rect(s, x, y0, col_w, Inches(0.55), fill=fill, line=None if highlight else LINE, line_width=Pt(0.75), radius=0.15)
    add_text(s, x, Emu(int(y0)+int(Inches(0.06))), col_w, Inches(0.42), label, size=15, color=txtcolor, bold=True, align=PP_ALIGN.CENTER)
    add_rect(s, x, Emu(int(y0)+int(Inches(0.65))), col_w, Inches(1.0), fill=BLUE_LIGHT if highlight else RGBColor(0xF4,0xF6,0xFA), radius=0.1)
    add_text(s, Emu(int(x)+int(Inches(0.08))), Emu(int(y0)+int(Inches(0.78))), Emu(int(col_w)-int(Inches(0.16))), Inches(0.8), desc, size=11.5, color=GRAY, align=PP_ALIGN.CENTER, line_spacing=1.3)

add_text(s, Inches(0.7), Inches(4.15), Inches(11.9), Inches(0.4),
          "今日の体験は「実装」「テスト」の部分にあたる", size=13, color=BLUE, bold=True)

# ラーメン比喩 2 cards
card_y = Inches(4.75)
card_w = Inches(5.85)
add_rect(s, Inches(0.7), card_y, card_w, Inches(1.9), fill=WHITE, line=LINE, line_width=Pt(0.75), radius=0.08)
add_text(s, Inches(1.0), Emu(int(card_y)+int(Inches(0.18))), Inches(5.2), Inches(0.4), "表の顔だけ ＝ カップラーメン", size=15, color=NAVY, bold=True)
add_bullets(s, Inches(1.0), Emu(int(card_y)+int(Inches(0.7))), Inches(5.2), Inches(1.1), [
    (0, "フロントエンド（HTML/CSS/JS）のみ"),
    (0, "すぐに提供できるが、全員に同じ味"),
], size=13.5)

x2 = Inches(6.75)
add_rect(s, x2, card_y, card_w, Inches(1.9), fill=BLUE, radius=0.08)
add_text(s, Emu(int(x2)+int(Inches(0.3))), Emu(int(card_y)+int(Inches(0.18))), Inches(5.2), Inches(0.4), "裏も合わせる ＝ ラーメン屋さん", size=15, color=WHITE, bold=True)
add_bullets(s, Emu(int(x2)+int(Inches(0.3))), Emu(int(card_y)+int(Inches(0.7))), Inches(5.2), Inches(1.1), [
    (0, "バックエンド（今回はサーバー処理）も"),
    (0, "時間はかかるが、お客さんに合わせられる"),
], size=13.5, color=RGBColor(0xE6,0xEC,0xFC))

add_footer(s, p, TOTAL)

# =========================================================
# Slide 4: コードと動作の因果関係
# =========================================================
s = add_slide()
p = next_page()
add_header(s, "STEP 1", "コードと動作の因果関係（コメントアウト体験）", p)

add_text(s, Inches(0.7), Inches(1.65), Inches(11.9), Inches(0.5),
          "「既存のコードを少し直す」「コメントを外す」操作で、\nコードのおかげでこう動く／直すと変わる、を体感する",
          size=15, color=GRAY, line_spacing=1.35)

cards = [
    ("ボタンの表示文字を変更", "upload.php",
     "テキストエディタでHTML内の\nボタンの文言を直接書き換え\n画面表示がその場で変わることを確認"),
    ("住所検索の仕組みを知る", "（概念説明）",
     "郵便番号を渡すと\n住所データが返ってくる仕組みを\n関数の考え方として紹介"),
    ("コメント投稿を有効化", "comments.php",
     "行頭の // （コメントアウト）を外すと\n投稿できなかったコメントが投稿できる\n「//以降は実行時に無視される」も説明"),
]
card_w = Inches(3.85)
gap = Inches(0.2)
x0 = Inches(0.7)
y0 = Inches(2.55)
for i, (title, file, body) in enumerate(cards):
    x = Emu(int(x0) + i*(int(card_w)+int(gap)))
    add_rect(s, x, y0, card_w, Inches(4.15), fill=WHITE, line=LINE, line_width=Pt(0.75), radius=0.06)
    add_rect(s, x, y0, card_w, Inches(0.08), fill=BLUE)
    add_text(s, Emu(int(x)+int(Inches(0.25))), Emu(int(y0)+int(Inches(0.3))), Emu(int(card_w)-int(Inches(0.5))), Inches(0.75),
              title, size=16, color=NAVY, bold=True, line_spacing=1.2)
    # file badge
    add_rect(s, Emu(int(x)+int(Inches(0.25))), Emu(int(y0)+int(Inches(1.15))), Inches(2.1), Inches(0.38), fill=BLUE_LIGHT, radius=0.3)
    add_text(s, Emu(int(x)+int(Inches(0.25))), Emu(int(y0)+int(Inches(1.19))), Inches(2.1), Inches(0.32), file, size=12, color=BLUE, bold=True, align=PP_ALIGN.CENTER)
    add_text(s, Emu(int(x)+int(Inches(0.25))), Emu(int(y0)+int(Inches(1.75))), Emu(int(card_w)-int(Inches(0.5))), Inches(2.1),
              body, size=13, color=GRAY, line_spacing=1.45)

add_footer(s, p, TOTAL)

# =========================================================
# Slide 5: 関数とは何か
# =========================================================
s = add_slide()
p = next_page()
add_header(s, "STEP 2", "関数とは何か", p)

add_text(s, Inches(0.7), Inches(1.7), Inches(11.9), Inches(0.5),
          "「郵便番号を渡すと住所が返ってくる」を例に、\n「呼び出す」と「結果が返ってくる」という関数の仕組みを説明する", size=15, color=GRAY, line_spacing=1.35)

# diagram: box - arrow - box - arrow - box
diagY = Inches(2.7)
boxW = Inches(2.6)
boxH = Inches(1.1)

# system box
add_rect(s, Inches(0.9), diagY, boxW, boxH, fill=NAVY, radius=0.1)
add_text(s, Inches(0.9), Emu(int(diagY)+int(Inches(0.35))), boxW, Inches(0.5), "システム", size=17, color=WHITE, bold=True, align=PP_ALIGN.CENTER)

# arrow 1 (request)
add_text(s, Inches(3.75), Emu(int(diagY)-int(Inches(0.35))), Inches(2.6), Inches(0.35), "郵便番号を渡す", size=12, color=GRAY, align=PP_ALIGN.CENTER)
arrow1 = s.shapes.add_shape(MSO_SHAPE.RIGHT_ARROW, Inches(3.75), Emu(int(diagY)+int(Inches(0.25))), Inches(2.4), Inches(0.5))
arrow1.fill.solid(); arrow1.fill.fore_color.rgb = BLUE; arrow1.line.fill.background(); arrow1.shadow.inherit = False

# function box
add_rect(s, Inches(6.4), diagY, boxW, boxH, fill=BLUE, radius=0.1)
add_text(s, Inches(6.4), Emu(int(diagY)+int(Inches(0.2))), boxW, Inches(0.4), "住所検索関数", size=17, color=WHITE, bold=True, align=PP_ALIGN.CENTER)
add_text(s, Inches(6.4), Emu(int(diagY)+int(Inches(0.62))), boxW, Inches(0.4), "（概念のイメージ）", size=12, color=RGBColor(0xE6,0xEC,0xFC), align=PP_ALIGN.CENTER)

# arrow 2 (response)
add_text(s, Inches(9.25), Emu(int(diagY)+int(Inches(1.3))), Inches(2.5), Inches(0.35), "住所データを返す", size=12, color=GRAY, align=PP_ALIGN.CENTER)
arrow2 = s.shapes.add_shape(MSO_SHAPE.RIGHT_ARROW, Inches(9.25), Emu(int(diagY)+int(Inches(1.65))), Inches(2.3), Inches(0.5))
arrow2.rotation = 180
arrow2.fill.solid(); arrow2.fill.fore_color.rgb = RGBColor(0x8A, 0xA6, 0xEC); arrow2.line.fill.background(); arrow2.shadow.inherit = False

# result back to system (draw a return box below)
add_rect(s, Inches(0.9), Emu(int(diagY)+int(Inches(1.9))), boxW, Inches(0.75), fill=BLUE_LIGHT, radius=0.1)
add_text(s, Inches(0.9), Emu(int(diagY)+int(Inches(2.08))), boxW, Inches(0.4), "呼び出し元で受け取る", size=12.5, color=NAVY, bold=True, align=PP_ALIGN.CENTER)

add_rect(s, Inches(0.7), Inches(5.35), Inches(11.9), Inches(1.3), fill=BLUE_LIGHT, radius=0.08)
add_text(s, Inches(1.0), Inches(5.55), Inches(11.2), Inches(1.0),
          "「機能をひとつのまとまりとして作っておき、必要なときに呼び出す」\nという考え方を理解する。これはAPI（外部に公開された呼び出し窓口）の考え方にも通じる。",
          size=14, color=NAVY, line_spacing=1.4)

add_footer(s, p, TOTAL)

# =========================================================
# Slide 6: データベースとは何か
# =========================================================
s = add_slide()
p = next_page()
add_header(s, "STEP 3", "データベースとは何か", p)

add_bullets(s, Inches(0.7), Inches(1.8), Inches(5.6), Inches(3.5), [
    (0, "コメント投稿機能を通じて、情報がデータとして\n保存・管理されていることを説明する"),
    (0, "住所検索の例も、郵便番号から住所データを\n探し出すデータベースの働きとして紹介する"),
    (0, "データを整理して扱いやすくすることも\nプログラマーの大事な仕事"),
], size=15, line_spacing=1.35, space_after=16)

# comment table card
tx = Inches(6.9)
ty = Inches(1.8)
add_rect(s, tx, ty, Inches(5.7), Inches(3.35), fill=WHITE, line=LINE, line_width=Pt(0.75), radius=0.06)
add_text(s, Emu(int(tx)+int(Inches(0.3))), Emu(int(ty)+int(Inches(0.25))), Inches(5.0), Inches(0.4), "コメントに必要な情報", size=15, color=BLUE, bold=True)

fields = ["コメントID", "投稿された動画", "投稿日時", "コメント本文"]
fy = Emu(int(ty)+int(Inches(0.85)))
for i, f in enumerate(fields):
    row = Emu(int(fy) + i*int(Inches(0.58)))
    add_rect(s, Emu(int(tx)+int(Inches(0.3))), row, Inches(0.14), Inches(0.14), fill=BLUE, radius=0.5)
    add_text(s, Emu(int(tx)+int(Inches(0.6))), Emu(int(row)-int(Inches(0.06))), Inches(4.6), Inches(0.4), f, size=14.5, color=GRAY)

add_footer(s, p, TOTAL)

# =========================================================
# Slide 7: 入力チェック（バリデーション）
# =========================================================
s = add_slide()
p = next_page()
add_header(s, "STEP 4", "入力チェック（バリデーション）", p)

# Before / After
by = Inches(1.85)
bw = Inches(5.85)
add_rect(s, Inches(0.7), by, bw, Inches(2.15), fill=RGBColor(0xFD, 0xF1, 0xF1), line=RGBColor(0xE9, 0xC7, 0xC7), line_width=Pt(0.75), radius=0.07)
add_text(s, Inches(1.0), Emu(int(by)+int(Inches(0.22))), Inches(5.2), Inches(0.4), "現状", size=14, color=RGBColor(0xB5, 0x3A, 0x3A), bold=True)
add_bullets(s, Inches(1.0), Emu(int(by)+int(Inches(0.75))), Inches(5.2), Inches(1.3), [
    (0, "コメント欄が空白のままでも投稿できてしまう"),
    (0, "実際のシステムでは入力漏れチェックが必須"),
], size=14, color=RGBColor(0x7A, 0x40, 0x40))

x2 = Inches(6.75)
add_rect(s, x2, by, bw, Inches(2.15), fill=RGBColor(0xEC, 0xF6, 0xEC), line=RGBColor(0xBE, 0xDD, 0xBE), line_width=Pt(0.75), radius=0.07)
add_text(s, Emu(int(x2)+int(Inches(0.3))), Emu(int(by)+int(Inches(0.22))), Inches(5.2), Inches(0.4), "解決方法", size=14, color=RGBColor(0x2E, 0x7D, 0x32), bold=True)
add_bullets(s, Emu(int(x2)+int(Inches(0.3))), Emu(int(by)+int(Inches(0.75))), Inches(5.2), Inches(1.3), [
    (0, "comments.php に短いPHPコードを追加"),
    (0, "if文で空文字かどうかをチェックし、\n空欄なら投稿を止める処理を書く"),
], size=14, color=RGBColor(0x3E, 0x6B, 0x40), line_spacing=1.3)

add_rect(s, Inches(0.7), Inches(4.35), Inches(11.9), Inches(1.1), fill=BLUE_LIGHT, radius=0.08)
add_text(s, Inches(1.0), Inches(4.6), Inches(11.2), Inches(0.7),
          "短いコードを実際に書き加えることで、入力チェックの仕組みを自分の手で作る体験にする",
          size=15, color=NAVY, bold=True)

add_footer(s, p, TOTAL)

# =========================================================
# Slide 8: アレンジ課題・準備のしやすさ
# =========================================================
s = add_slide()
p = next_page()
add_header(s, "STEP 5-6", "アレンジ課題・準備のしやすさ", p)

card_w = Inches(5.85)
y0 = Inches(1.9)
add_rect(s, Inches(0.7), y0, card_w, Inches(3.9), fill=WHITE, line=LINE, line_width=Pt(0.75), radius=0.06)
add_text(s, Inches(1.0), Emu(int(y0)+int(Inches(0.3))), Inches(5.2), Inches(0.5), "5. アレンジ課題（任意・発展）", size=17, color=NAVY, bold=True)
add_bullets(s, Inches(1.0), Emu(int(y0)+int(Inches(1.05))), Inches(5.2), Inches(2.5), [
    (0, "コメント表示部分の文言を自分で工夫して変更"),
    (1, "例：「さん」を「様」に変更"),
    (0, "「言われた通り」ではなく自分で考えて直す体験"),
], size=14.5, line_spacing=1.4, space_after=14)

x2 = Inches(6.75)
add_rect(s, x2, y0, card_w, Inches(3.9), fill=NAVY, radius=0.06)
add_text(s, Emu(int(x2)+int(Inches(0.3))), Emu(int(y0)+int(Inches(0.3))), Inches(5.2), Inches(0.5), "6. 準備のしやすさ", size=17, color=WHITE, bold=True)
add_bullets(s, Emu(int(x2)+int(Inches(0.3))), Emu(int(y0)+int(Inches(1.05))), Inches(5.2), Inches(2.5), [
    (0, "start-mac.sh / start-win.bat を実行するだけ"),
    (0, "VSCodeとPHPさえ入っていれば\nその場で動画共有サイトが起動する"),
], size=14.5, color=RGBColor(0xD6,0xDE,0xEE), line_spacing=1.4, space_after=14)

add_footer(s, p, TOTAL)

# =========================================================
# Slide 9-10: 体験の流れ（想定）
# =========================================================
flow_all = [
    "プログラミングとは何か、フロントエンドとバックエンドの違いを説明",
    "start-mac.sh などでシステムを起動し、動画共有サイトの画面を確認",
    "ログイン画面→新規登録画面へ。投稿ボタンの表示が分かりにくいことに気づく",
    "upload.php のボタン文言をテキストエディタで直接修正→再読み込みで確認",
    "住所検索の仕組み（郵便番号→住所データ）を関数の考え方として説明",
    "会員登録を行いログイン状態にする（閉じる前にログアウトを案内）",
    "コメント投稿を試すが、投稿できないことを確認",
    "comments.php の該当行から // を外し、コメント投稿を有効化→再確認",
    "（任意）コメント表示の文言をアレンジしてみる",
    "コメントに必要な情報からデータベースの考え方を説明する",
    "入力漏れチェックがないことを確認し、短いPHPコードを一緒に書き加える",
]

def flow_slide(items_slice, start_no, subtitle):
    s = add_slide()
    p = next_page()
    add_header(s, "FLOW", "体験の流れ（想定）", p)
    add_text(s, Inches(0.7), Inches(1.6), Inches(11.9), Inches(0.4), subtitle, size=13, color=BLUE, bold=True)
    y = Inches(2.15)
    row_h = Inches(0.83)
    for i, text in enumerate(items_slice):
        no = start_no + i
        ry = Emu(int(y) + i*int(row_h))
        # number circle
        add_rect(s, Inches(0.7), ry, Inches(0.55), Inches(0.55), fill=BLUE, radius=0.5)
        add_text(s, Inches(0.7), Emu(int(ry)+int(Inches(0.11))), Inches(0.55), Inches(0.4), str(no), size=16, color=WHITE, bold=True, align=PP_ALIGN.CENTER)
        add_rect(s, Inches(1.45), Emu(int(ry)-int(Inches(0.02))), Inches(11.1), Inches(0.62), fill=WHITE, line=LINE, line_width=Pt(0.75), radius=0.15)
        add_text(s, Inches(1.7), Emu(int(ry)+int(Inches(0.09))), Inches(10.6), Inches(0.45), text, size=13.5, color=NAVY)
    add_footer(s, p, TOTAL)

flow_slide(flow_all[0:6], 1, "STEP 1〜6：導入からログインまで")
flow_slide(flow_all[6:11], 7, "STEP 7〜11：コメント投稿から入力チェックまで")

# =========================================================
# Slide 12: クロージング
# =========================================================
s = add_slide()
p = next_page()
add_rect(s, 0, 0, SW, Inches(7.5), fill=NAVY)
add_rect(s, 0, 0, SW, Inches(0.12), fill=BLUE)
add_text(s, Inches(0.9), Inches(2.9), Inches(11), Inches(1.0),
          "小さな修正の積み重ねが、\nWebアプリを動かす力になる。",
          size=30, color=WHITE, bold=True, line_spacing=1.3, align=PP_ALIGN.LEFT)
add_rect(s, Inches(0.9), Inches(4.35), Inches(0.7), Pt(5), fill=BLUE)
add_text(s, Inches(0.9), Inches(4.65), Inches(10), Inches(0.5),
          "ご参加ありがとうございました", size=15, color=RGBColor(0xC7, 0xD2, 0xEE))
add_footer(s, p, TOTAL)

out_path = "/Users/shoyabushita/Desktop/web_taiken/Webプログラミング体験_資料.pptx"
prs.save(out_path)
print("saved:", out_path, "slides:", len(prs.slides.__iter__.__self__._sldIdLst))
