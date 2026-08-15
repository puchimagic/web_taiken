# -*- coding: utf-8 -*-
from pptx import Presentation
from pptx.util import Inches, Pt, Emu
from pptx.dml.color import RGBColor
from pptx.enum.text import PP_ALIGN, MSO_ANCHOR
from pptx.enum.shapes import MSO_SHAPE
from pptx.oxml.ns import qn
import copy

# ---- サイトのデザイントークン（public/style.css 準拠） ----
MAIN = RGBColor(0xB5, 0x55, 0x1F)       # --main-color テラコッタ
MAIN_HOVER = RGBColor(0x96, 0x46, 0x1A) # --main-color-hover
BG = RGBColor(0xF6, 0xF1, 0xE6)         # --bg
SURFACE = RGBColor(0xFF, 0xFD, 0xF8)    # --surface
SURFACE2 = RGBColor(0xEF, 0xE6, 0xD3)   # --surface-2
BORDER = RGBColor(0xDD, 0xCC, 0xAC)     # --border
INK = RGBColor(0x2C, 0x24, 0x18)        # --ink
SUB = RGBColor(0x7A, 0x6D, 0x54)        # --sub
STAMP = RGBColor(0x8A, 0x73, 0x55)      # --stamp
CREAM = RGBColor(0xFF, 0xF8, 0xEC)      # main上の文字色
TAGBG = RGBColor(0xF7, 0xE9, 0xDC)
TAGBORDER = RGBColor(0xEC, 0xD3, 0xBA)

FONT_SERIF = "Hiragino Mincho ProN"
FONT_SANS = "Hiragino Kaku Gothic ProN"

SLIDE_W = Inches(13.333)
SLIDE_H = Inches(7.5)

prs = Presentation()
prs.slide_width = SLIDE_W
prs.slide_height = SLIDE_H
BLANK = prs.slide_layouts[6]


def add_slide():
    return prs.slides.add_slide(BLANK)


def set_bg(slide, color=BG):
    slide.background.fill.solid()
    slide.background.fill.fore_color.rgb = color


def no_line(shape):
    shape.line.fill.background()


def rect(slide, l, t, w, h, fill=None, line=None, line_w=None, shadow=False, radius=None):
    shape_type = MSO_SHAPE.ROUNDED_RECTANGLE if radius else MSO_SHAPE.RECTANGLE
    sp = slide.shapes.add_shape(shape_type, l, t, w, h)
    if radius:
        try:
            sp.adjustments[0] = radius
        except Exception:
            pass
    if fill is None:
        sp.fill.background()
    else:
        sp.fill.solid()
        sp.fill.fore_color.rgb = fill
    if line is None:
        sp.line.fill.background()
    else:
        sp.line.color.rgb = line
        sp.line.width = line_w or Pt(1)
    sp.shadow.inherit = False
    return sp


def pill(slide, l, t, w, h, fill=None, line=None, line_w=None):
    sp = slide.shapes.add_shape(MSO_SHAPE.ROUNDED_RECTANGLE, l, t, w, h)
    try:
        sp.adjustments[0] = 0.5
    except Exception:
        pass
    if fill is None:
        sp.fill.background()
    else:
        sp.fill.solid()
        sp.fill.fore_color.rgb = fill
    if line is None:
        sp.line.fill.background()
    else:
        sp.line.color.rgb = line
        sp.line.width = line_w or Pt(1)
    sp.shadow.inherit = False
    return sp


def textbox(slide, l, t, w, h, text, size=14, color=INK, bold=False, align=PP_ALIGN.LEFT,
            font=FONT_SANS, anchor=MSO_ANCHOR.TOP, line_spacing=1.0, wrap=True, italic=False,
            letter_spacing=None):
    tb = slide.shapes.add_textbox(l, t, w, h)
    tf = tb.text_frame
    tf.word_wrap = wrap
    tf.vertical_anchor = anchor
    tf.margin_left = 0
    tf.margin_right = 0
    tf.margin_top = 0
    tf.margin_bottom = 0
    lines = text.split("\n")
    for i, line in enumerate(lines):
        p = tf.paragraphs[0] if i == 0 else tf.add_paragraph()
        p.alignment = align
        p.line_spacing = line_spacing
        run = p.add_run()
        run.text = line
        run.font.size = Pt(size)
        run.font.bold = bold
        run.font.italic = italic
        run.font.color.rgb = color
        run.font.name = font
    return tb


def multi_run_para(tf, first, runs_specs, align=PP_ALIGN.LEFT, line_spacing=1.0):
    p = tf.paragraphs[0] if first else tf.add_paragraph()
    p.alignment = align
    p.line_spacing = line_spacing
    for txt, size, color, bold, font in runs_specs:
        run = p.add_run()
        run.text = txt
        run.font.size = Pt(size)
        run.font.bold = bold
        run.font.color.rgb = color
        run.font.name = font
    return p


def footer(slide, page_no, total=15, label="Webプログラミング体験授業"):
    textbox(slide, Inches(0.5), Inches(7.12), Inches(6), Inches(0.3),
            label, size=9, color=SUB, font=FONT_SANS)
    textbox(slide, Inches(12.0), Inches(7.12), Inches(0.9), Inches(0.3),
            f"{page_no} / {total}", size=9, color=SUB, font=FONT_SANS, align=PP_ALIGN.RIGHT)


def kicker(slide, text):
    """左上の小さい見出しラベル（例：STEP 1）"""
    bar = rect(slide, Inches(0.5), Inches(0.42), Inches(0.06), Inches(0.28), fill=MAIN)
    textbox(slide, Inches(0.66), Inches(0.4), Inches(4), Inches(0.32),
            text, size=13, color=MAIN, bold=True, font=FONT_SANS, letter_spacing=True)


def title(slide, text, size=28):
    textbox(slide, Inches(0.5), Inches(0.72), Inches(11.5), Inches(0.8),
            text, size=size, color=INK, bold=True, font=FONT_SERIF)


def brand_dot(slide, l, t, d=Inches(0.3)):
    dot = slide.shapes.add_shape(MSO_SHAPE.OVAL, l, t, d, d)
    dot.fill.solid()
    dot.fill.fore_color.rgb = MAIN
    dot.line.fill.background()
    dot.shadow.inherit = False
    tf = dot.text_frame
    tf.margin_left = 0; tf.margin_right = 0; tf.margin_top = 0; tf.margin_bottom = 0
    tf.word_wrap = False
    p = tf.paragraphs[0]
    p.alignment = PP_ALIGN.CENTER
    run = p.add_run()
    run.text = "旅"
    run.font.size = Pt(12)
    run.font.bold = True
    run.font.color.rgb = CREAM
    run.font.name = FONT_SANS
    return dot


def card(slide, l, t, w, h, fill=SURFACE, line=BORDER, radius=0.05):
    c = rect(slide, l, t, w, h, fill=fill, line=line, line_w=Pt(1), radius=radius)
    return c


def step_badge(slide, l, t, num, label):
    """円形バッジ＋ラベル"""
    d = Inches(0.42)
    dot = slide.shapes.add_shape(MSO_SHAPE.OVAL, l, t, d, d)
    dot.fill.solid()
    dot.fill.fore_color.rgb = MAIN
    dot.line.fill.background()
    dot.shadow.inherit = False
    tf = dot.text_frame
    tf.margin_left = 0; tf.margin_right = 0; tf.margin_top = 0; tf.margin_bottom = 0
    p = tf.paragraphs[0]
    p.alignment = PP_ALIGN.CENTER
    run = p.add_run()
    run.text = str(num)
    run.font.size = Pt(15)
    run.font.bold = True
    run.font.color.rgb = CREAM
    run.font.name = FONT_SANS


def code_chip(slide, l, t, w, h, text):
    c = rect(slide, l, t, w, h, fill=INK, radius=0.15)
    textbox(slide, l, t, w, h, text, size=13, color=RGBColor(0xF7, 0xE9, 0xDC), bold=False,
            align=PP_ALIGN.CENTER, font="Courier New", anchor=MSO_ANCHOR.MIDDLE)
    return c


def arrow(slide, l, t, w, h, fill=STAMP):
    sp = slide.shapes.add_shape(MSO_SHAPE.RIGHT_ARROW, l, t, w, h)
    sp.fill.solid()
    sp.fill.fore_color.rgb = fill
    sp.line.fill.background()
    sp.shadow.inherit = False
    return sp


def tag_badge(slide, l, t, w, h, text):
    c = pill(slide, l, t, w, h, fill=TAGBG, line=TAGBORDER, line_w=Pt(0.75))
    textbox(slide, l, t, w, h, text, size=12, color=MAIN, bold=False, align=PP_ALIGN.CENTER,
            font=FONT_SANS, anchor=MSO_ANCHOR.MIDDLE)
    return c


def bullet_block(slide, l, t, w, h, lines, size=13.5, color=INK, gap=0.42, bold_first=False, marker="●"):
    tb = slide.shapes.add_textbox(l, t, w, h)
    tf = tb.text_frame
    tf.word_wrap = True
    for i, line in enumerate(lines):
        p = tf.paragraphs[0] if i == 0 else tf.add_paragraph()
        p.line_spacing = 1.25
        p.space_after = Pt(10)
        run = p.add_run()
        run.text = f"{marker}  {line}"
        run.font.size = Pt(size)
        run.font.color.rgb = color
        run.font.name = FONT_SANS
    return tb


# ============================================================
# スライド 1: タイトル
# ============================================================
s = add_slide()
set_bg(s, BG)
# 装飾: 右下・左上に淡い円
c1 = slide_deco = s.shapes.add_shape(MSO_SHAPE.OVAL, Inches(-2), Inches(-2), Inches(5), Inches(5))
c1.fill.solid(); c1.fill.fore_color.rgb = SURFACE2; c1.fill.transparency = 0
c1.line.fill.background(); c1.shadow.inherit = False
# 半透明感を出すため薄い色に留める
c1.fill.fore_color.rgb = RGBColor(0xEF, 0xE6, 0xD3)

c2 = s.shapes.add_shape(MSO_SHAPE.OVAL, Inches(10.8), Inches(4.8), Inches(4.2), Inches(4.2))
c2.fill.solid(); c2.fill.fore_color.rgb = RGBColor(0xF7, 0xE9, 0xDC)
c2.line.fill.background(); c2.shadow.inherit = False

brand_dot(s, Inches(0.9), Inches(0.9), Inches(0.5))
textbox(s, Inches(1.55), Inches(0.9), Inches(4), Inches(0.5), "旅ノート | Web体験版",
        size=15, color=SUB, bold=True, font=FONT_SANS, anchor=MSO_ANCHOR.MIDDLE)

textbox(s, Inches(0.9), Inches(2.5), Inches(11.5), Inches(1.3),
        "Webプログラミング体験授業", size=42, color=INK, bold=True, font=FONT_SERIF)
textbox(s, Inches(0.9), Inches(3.5), Inches(11.5), Inches(1.0),
        "「旅行スポット共有サイト」を、少しずつ完成させよう", size=20, color=MAIN, bold=True, font=FONT_SERIF)

line = rect(s, Inches(0.9), Inches(4.35), Inches(2.4), Pt(3), fill=MAIN)

textbox(s, Inches(0.9), Inches(4.65), Inches(9), Inches(0.6),
        "コードを直すと、Webアプリの動きが変わる。それを自分の手で体感しよう。",
        size=15, color=SUB, font=FONT_SANS)

# 下部：使用ツールのタグ
tools = ["VSCode", "PHP", "SQLite", "HTML / CSS / JS"]
x = Inches(0.9)
for t_ in tools:
    w = Inches(0.5 + len(t_) * 0.11)
    tag_badge(s, x, Inches(6.3), w, Inches(0.42), t_)
    x = Emu(x + w + Inches(0.18))

footer(s, 1)

# ============================================================
# スライド 2: 今日やること（アジェンダ）
# ============================================================
s = add_slide()
set_bg(s)
kicker(s, "AGENDA")
title(s, "今日やること")
textbox(s, Inches(0.5), Inches(1.35), Inches(10.5), Inches(0.5),
        "旅行スポット共有サイトの「未完成な部分」を、順番に完成させていきます",
        size=14, color=SUB, font=FONT_SANS)

items = [
    ("0", "プログラミングって何？", "料理づくりに例えて、システム開発の流れをつかむ"),
    ("1", "コードを直してみよう", "ボタンの文字を変える／コメントアウトを外す"),
    ("2", "APIってなに？", "現在地から住所が自動で入力される仕組みを体験"),
    ("3", "データベースって何？", "投稿やコメントの情報がどう保存されているか"),
    ("4", "入力チェックを作ろう", "空欄のまま投稿できてしまう問題を自分で直す"),
    ("5", "自由にアレンジ", "表示の文言などを自分の工夫で変えてみる"),
]

col_w = Inches(3.9)
row_h = Inches(1.55)
start_x = Inches(0.5)
start_y = Inches(2.05)
gap_x = Inches(0.18)
gap_y = Inches(0.2)

for i, (num, ttl, desc) in enumerate(items):
    row = i // 3
    col = i % 3
    x = Emu(start_x + col * (col_w + gap_x))
    y = Emu(start_y + row * (row_h + gap_y))
    card(s, x, y, col_w, row_h)
    step_badge(s, Emu(x + Inches(0.22)), Emu(y + Inches(0.2)), num, "")
    textbox(s, Emu(x + Inches(0.8)), Emu(y + Inches(0.18)), Emu(col_w - Inches(1.0)), Inches(0.5),
            ttl, size=14.5, color=INK, bold=True, font=FONT_SANS)
    textbox(s, Emu(x + Inches(0.22)), Emu(y + Inches(0.78)), Emu(col_w - Inches(0.4)), Inches(0.7),
            desc, size=11.5, color=SUB, font=FONT_SANS, line_spacing=1.25)

footer(s, 2)

# ============================================================
# スライド 3: プログラミングって何？（料理の比喩）
# ============================================================
s = add_slide()
set_bg(s)
kicker(s, "STEP 0")
title(s, "プログラミングって何？")
textbox(s, Inches(0.5), Inches(1.35), Inches(11), Inches(0.5),
        "システムをつくる流れは、「料理をつくる」流れとよく似ています",
        size=14, color=SUB, font=FONT_SANS)

steps = [
    ("要件定義", "メニューを決める"),
    ("設計", "レシピを考える"),
    ("実装", "調理する"),
    ("テスト", "味見して直す"),
    ("運用保守", "食事・片付け"),
]
n = len(steps)
total_w = Inches(11.3)
box_w = Inches(1.95)
gap = Emu((total_w - box_w * n) // (n - 1))
x = Inches(0.5)
y = Inches(2.35)
box_h = Inches(1.5)

for i, (top_label, bottom_label) in enumerate(steps):
    card(s, x, y, box_w, box_h, fill=SURFACE if i != 2 else SURFACE2, line=BORDER if i != 2 else MAIN)
    textbox(s, x, Emu(y + Inches(0.18)), box_w, Inches(0.4), top_label, size=13.5, bold=True,
            color=INK if i != 2 else MAIN, align=PP_ALIGN.CENTER, font=FONT_SANS)
    rect(s, Emu(x + Inches(0.65)), Emu(y + Inches(0.62)), Inches(0.65), Pt(1.5), fill=BORDER)
    textbox(s, Emu(x + Inches(0.08)), Emu(y + Inches(0.82)), Emu(box_w - Inches(0.16)), Inches(0.6),
            bottom_label, size=11.5, color=SUB, align=PP_ALIGN.CENTER, font=FONT_SANS, line_spacing=1.15)
    if i < n - 1:
        arrow(s, Emu(x + box_w + Inches(0.04)), Emu(y + Inches(0.6)), Emu(gap - Inches(0.08)), Inches(0.3))
    x = Emu(x + box_w + gap)

badge = pill(s, Inches(3.85), Inches(4.35), Inches(5.6), Inches(0.55), fill=MAIN)
textbox(s, Inches(3.85), Inches(4.35), Inches(5.6), Inches(0.55),
        "今日は「実装」「テスト」の部分を体験します", size=14.5, color=CREAM, bold=True,
        align=PP_ALIGN.CENTER, font=FONT_SANS, anchor=MSO_ANCHOR.MIDDLE)

footer(s, 3)

# ============================================================
# スライド 4: 表の顔・裏の顔（ラーメン比喩）
# ============================================================
s = add_slide()
set_bg(s)
kicker(s, "STEP 0")
title(s, "Webページの「表の顔」と「裏の顔」")
textbox(s, Inches(0.5), Inches(1.35), Inches(11), Inches(0.5),
        "見えている部分（フロントエンド）と、見えない部分（バックエンド）があります",
        size=14, color=SUB, font=FONT_SANS)

# 左カード：カップラーメン
lx, ly, lw, lh = Inches(0.5), Inches(2.1), Inches(5.55), Inches(4.5)
card(s, lx, ly, lw, lh)
rect(s, lx, ly, lw, Inches(0.1), fill=STAMP)
textbox(s, Emu(lx + Inches(0.35)), Emu(ly + Inches(0.3)), Emu(lw - Inches(0.7)), Inches(0.45),
        "🍜 表の顔だけ＝カップラーメン", size=17, color=INK, bold=True, font=FONT_SANS)
textbox(s, Emu(lx + Inches(0.35)), Emu(ly + Inches(0.85)), Emu(lw - Inches(0.7)), Inches(0.4),
        "フロントエンド（HTML / CSS / JavaScript）のみ", size=12.5, color=SUB, font=FONT_SANS)
bullet_block(s, Emu(lx + Inches(0.35)), Emu(ly + Inches(1.5)), Emu(lw - Inches(0.7)), Inches(2.6),
             ["すぐに提供できる", "だれに対しても同じ表示・同じ動き", "サーバー側の処理がない＝\n個人に合わせた表示はできない"],
             size=13)

# 右カード：ラーメン屋
rx, ry, rw, rh = Inches(6.28), Inches(2.1), Inches(5.55), Inches(4.5)
card(s, rx, ry, rw, rh, fill=RGBColor(0xF7, 0xE9, 0xDC), line=MAIN)
rect(s, rx, ry, rw, Inches(0.1), fill=MAIN)
textbox(s, Emu(rx + Inches(0.35)), Emu(ry + Inches(0.3)), Emu(rw - Inches(0.7)), Inches(0.45),
        "🍜 裏の顔まで＝ラーメン屋さん", size=17, color=INK, bold=True, font=FONT_SANS)
textbox(s, Emu(rx + Inches(0.35)), Emu(ry + Inches(0.85)), Emu(rw - Inches(0.7)), Inches(0.4),
        "＋ バックエンド（今回は PHP）", size=12.5, color=MAIN, bold=True, font=FONT_SANS)
bullet_block(s, Emu(rx + Inches(0.35)), Emu(ry + Inches(1.5)), Emu(rw - Inches(0.7)), Inches(2.6),
             ["提供までに少し時間がかかる", "お客さんに合わせた味・トッピングができる", "サーバー側でデータを処理\n＝一人ひとりに合わせた表示ができる"],
             size=13)

textbox(s, Inches(0.5), Inches(6.75), Inches(11.3), Inches(0.4),
        "今日は、この「厨房エリア（バックエンド）」を覗いてみましょう！",
        size=13.5, color=MAIN, bold=True, align=PP_ALIGN.CENTER, font=FONT_SANS)

footer(s, 4)

# ============================================================
# スライド 5: サイトを起動してみよう
# ============================================================
s = add_slide()
set_bg(s)
kicker(s, "STEP 0 → 1")
title(s, "サイトを起動してみよう")

card(s, Inches(0.5), Inches(1.85), Inches(11.3), Inches(1.15))
textbox(s, Inches(0.85), Inches(2.05), Inches(2.2), Inches(0.75),
        "起動方法", size=14, color=MAIN, bold=True, font=FONT_SANS, anchor=MSO_ANCHOR.MIDDLE)
code_chip(s, Inches(3.1), Inches(2.15), Inches(3.3), Inches(0.55), "start-mac.sh")
textbox(s, Inches(6.55), Inches(2.15), Inches(0.6), Inches(0.55), "/", size=16, color=SUB,
        align=PP_ALIGN.CENTER, anchor=MSO_ANCHOR.MIDDLE)
code_chip(s, Inches(7.2), Inches(2.15), Inches(3.9), Inches(0.55), "start-win.bat")

bullet_block(s, Inches(0.85), Inches(3.35), Inches(5.4), Inches(3.2),
             ["ダブルクリック（または実行）するだけでサイトが起動", "ブラウザで表示されたURLを開く", "VSCodeとPHPさえ入っていれば準備完了"],
             size=14)

rx, ry, rw, rh = Inches(6.55), Inches(3.35), Inches(5.25), Inches(3.2)
card(s, rx, ry, rw, rh, fill=SURFACE2)
textbox(s, Emu(rx + Inches(0.3)), Emu(ry + Inches(0.25)), Emu(rw - Inches(0.6)), Inches(0.4),
        "今日たどる画面の流れ", size=13.5, bold=True, color=INK, font=FONT_SANS)
flow = ["ログイン画面", "新規登録画面", "会員登録 → ログイン", "投稿ページ（スポット投稿）", "ホーム画面（一覧・検索）"]
fy = ry + Inches(0.85)
for i, f in enumerate(flow):
    step_badge(s, Emu(rx + Inches(0.3)), Emu(fy), i + 1, "")
    textbox(s, Emu(rx + Inches(0.85)), Emu(fy + Inches(0.02)), Emu(rw - Inches(1.1)), Inches(0.4),
            f, size=12.5, color=INK, font=FONT_SANS, anchor=MSO_ANCHOR.MIDDLE)
    fy = Emu(fy + Inches(0.44))

footer(s, 5)

# ============================================================
# スライド 6: ボタンの文字を変えてみよう
# ============================================================
s = add_slide()
set_bg(s)
kicker(s, "STEP 1")
title(s, "ボタンの文字を変えてみよう")
textbox(s, Inches(0.5), Inches(1.35), Inches(11), Inches(0.5),
        "投稿ボタンの表示が分かりにくい…テキストエディタで直接直してみよう",
        size=14, color=SUB, font=FONT_SANS)

card(s, Inches(0.5), Inches(2.05), Inches(11.3), Inches(1.75))
code_chip(s, Inches(0.85), Inches(2.35), Inches(2.7), Inches(0.5), "upload.php")
textbox(s, Inches(3.75), Inches(2.35), Inches(7.7), Inches(0.5),
        "HTML内のボタンの文言を書き換える", size=15, color=INK, bold=True, font=FONT_SANS,
        anchor=MSO_ANCHOR.MIDDLE)
textbox(s, Inches(0.85), Inches(3.0), Inches(10.6), Inches(0.6),
        "コードを保存して、ブラウザを再読み込みするだけ。画面の表示がその場で変わることを確認しよう。",
        size=13, color=SUB, font=FONT_SANS)

# Before/After
bx, by, bw, bh = Inches(0.85), Inches(4.1), Inches(5.0), Inches(2.0)
card(s, bx, by, bw, bh, fill=SURFACE2)
textbox(s, Emu(bx + Inches(0.25)), Emu(by + Inches(0.2)), Emu(bw - Inches(0.5)), Inches(0.35),
        "BEFORE", size=12, color=SUB, bold=True, font=FONT_SANS)
pill(s, Emu(bx + Inches(0.25)), Emu(by + Inches(0.75)), Inches(2.4), Inches(0.6), fill=BORDER)
textbox(s, Emu(bx + Inches(0.25)), Emu(by + Inches(0.75)), Inches(2.4), Inches(0.6),
        "ボタン", size=14, color=INK, align=PP_ALIGN.CENTER, anchor=MSO_ANCHOR.MIDDLE, font=FONT_SANS)

arrow(s, Inches(6.0), Inches(4.85), Inches(0.85), Inches(0.5), fill=MAIN)

ax, ay, aw, ah = Inches(7.0), Inches(4.1), Inches(5.0), Inches(2.0)
card(s, ax, ay, aw, ah, fill=RGBColor(0xF7, 0xE9, 0xDC), line=MAIN)
textbox(s, Emu(ax + Inches(0.25)), Emu(ay + Inches(0.2)), Emu(aw - Inches(0.5)), Inches(0.35),
        "AFTER", size=12, color=MAIN, bold=True, font=FONT_SANS)
pill(s, Emu(ax + Inches(0.25)), Emu(ay + Inches(0.75)), Inches(2.4), Inches(0.6), fill=MAIN)
textbox(s, Emu(ax + Inches(0.25)), Emu(ay + Inches(0.75)), Inches(2.4), Inches(0.6),
        "投稿する", size=14, color=CREAM, align=PP_ALIGN.CENTER, anchor=MSO_ANCHOR.MIDDLE, font=FONT_SANS)

footer(s, 6)

# ============================================================
# スライド 7: コメントアウトって何？
# ============================================================
s = add_slide()
set_bg(s)
kicker(s, "STEP 1")
title(s, "コメントアウトって何？")
textbox(s, Inches(0.5), Inches(1.35), Inches(11), Inches(0.5),
        "コメントを投稿してみよう…あれ、投稿できない！",
        size=14, color=SUB, font=FONT_SANS)

card(s, Inches(0.5), Inches(2.05), Inches(11.3), Inches(1.7))
code_chip(s, Inches(0.85), Inches(2.35), Inches(3.2), Inches(0.5), "comments.php")
textbox(s, Inches(4.25), Inches(2.35), Inches(7.2), Inches(0.5),
        "先頭に付いている // を外してみよう", size=15, color=INK, bold=True, font=FONT_SANS,
        anchor=MSO_ANCHOR.MIDDLE)
textbox(s, Inches(0.85), Inches(3.0), Inches(10.6), Inches(0.6),
        "たった2文字を消すだけで、投稿できなかったコメントが投稿できるようになる。",
        size=13, color=SUB, font=FONT_SANS)

# コードブロック風
codebox = rect(s, Inches(0.85), Inches(4.05), Inches(11.0), Inches(1.55), fill=INK, radius=0.06)
tf = codebox.text_frame
tf.word_wrap = True
tf.margin_left = Inches(0.3); tf.margin_top = Inches(0.22); tf.margin_right = Inches(0.3)
p1 = tf.paragraphs[0]
p1.line_spacing = 1.4
r1 = p1.add_run(); r1.text = "// この行の後ろは、実行されるときに無視されます"
r1.font.name = "Courier New"; r1.font.size = Pt(14); r1.font.color.rgb = RGBColor(0x9A, 0x9A, 0x8A)
p2 = tf.add_paragraph(); p2.line_spacing = 1.4
r2 = p2.add_run(); r2.text = 'echo "Hello, World!";  '
r2.font.name = "Courier New"; r2.font.size = Pt(14); r2.font.color.rgb = RGBColor(0xF7, 0xE9, 0xDC)
r2b = p2.add_run(); r2b.text = "// ← ここから先はコメント（無視される）"
r2b.font.name = "Courier New"; r2b.font.size = Pt(13); r2b.font.color.rgb = RGBColor(0x9A, 0x9A, 0x8A)

footer(s, 7)

# ============================================================
# スライド 8: APIってなに？（現在地→住所）
# ============================================================
s = add_slide()
set_bg(s)
kicker(s, "STEP 2")
title(s, "APIってなに？")
textbox(s, Inches(0.5), Inches(1.35), Inches(11), Inches(0.5),
        "「📍 現在地を取得」ボタンを押すと、住所が自動で入力される仕組みを見てみよう",
        size=14, color=SUB, font=FONT_SANS)

# フロー図：4ステップ
flow_items = [
    ("📍", "ブラウザが\n現在地(緯度・経度)を取得"),
    ("🖥", "サーバー(geocode.php)が\n外部APIに問い合わせ"),
    ("🌏", "外部の住所検索API\n(OpenStreetMap)"),
    ("📝", "住所が\n自動入力される"),
]
n = len(flow_items)
box_w = Inches(2.55)
total_w = Inches(11.3)
gap = Emu((total_w - box_w * n) // (n - 1))
x = Inches(0.5)
y = Inches(2.4)
box_h = Inches(2.1)

for i, (emoji, label) in enumerate(flow_items):
    fill = RGBColor(0xF7, 0xE9, 0xDC) if i == 2 else SURFACE
    line = MAIN if i == 2 else BORDER
    card(s, x, y, box_w, box_h, fill=fill, line=line)
    textbox(s, x, Emu(y + Inches(0.25)), box_w, Inches(0.7), emoji, size=32, align=PP_ALIGN.CENTER, font=FONT_SANS)
    textbox(s, Emu(x + Inches(0.15)), Emu(y + Inches(1.05)), Emu(box_w - Inches(0.3)), Inches(0.95),
            label, size=12, color=INK, align=PP_ALIGN.CENTER, font=FONT_SANS, line_spacing=1.25)
    if i < n - 1:
        arrow(s, Emu(x + box_w + Inches(0.04)), Emu(y + box_h/2 - Inches(0.15)), Emu(gap - Inches(0.08)), Inches(0.3))
    x = Emu(x + box_w + gap)

textbox(s, Inches(0.5), Inches(4.85), Inches(11.3), Inches(0.9),
        "「URLを叩けばデータが返ってくる」——これが外部API（Application Programming Interface）の仕組み。\nブラウザの機能とサーバーの機能が連携して、1つの機能をつくっています。",
        size=13.5, color=SUB, font=FONT_SANS, line_spacing=1.4, align=PP_ALIGN.CENTER)

footer(s, 8)

# ============================================================
# スライド 9: 裏側で起きていること（関数の考え方）
# ============================================================
s = add_slide()
set_bg(s)
kicker(s, "STEP 2")
title(s, "裏側で起きていること：関数の考え方")
textbox(s, Inches(0.5), Inches(1.35), Inches(11), Inches(0.5),
        "「郵便番号（緯度経度）を渡すと、住所が返ってくる」——これが「関数」です",
        size=14, color=SUB, font=FONT_SANS)

cy = Inches(2.6)
ch = Inches(1.6)
# システム（呼び出す側）
card(s, Inches(0.5), cy, Inches(3.0), ch, fill=SURFACE2)
textbox(s, Inches(0.5), Emu(cy + Inches(0.15)), Inches(3.0), Inches(0.4), "あなた", size=14, bold=True,
        color=INK, align=PP_ALIGN.CENTER, font=FONT_SANS)
textbox(s, Inches(0.5), Emu(cy + Inches(0.6)), Inches(3.0), Inches(0.9), "「住所教えて」\nと呼び出す", size=12.5,
        color=SUB, align=PP_ALIGN.CENTER, font=FONT_SANS, line_spacing=1.2)

arrow(s, Inches(3.65), Emu(cy + Inches(0.15)), Inches(1.15), Inches(0.4))
textbox(s, Inches(3.55), Emu(cy - Inches(0.32)), Inches(1.4), Inches(0.35), "緯度経度を渡す",
        size=10.5, color=SUB, align=PP_ALIGN.CENTER, font=FONT_SANS)

# 関数（住所検索）
fx = Inches(4.95)
card(s, fx, cy, Inches(3.35), ch, fill=RGBColor(0xF7, 0xE9, 0xDC), line=MAIN)
textbox(s, fx, Emu(cy + Inches(0.15)), Inches(3.35), Inches(0.4), "住所検索の機能", size=14, bold=True,
        color=MAIN, align=PP_ALIGN.CENTER, font=FONT_SANS)
textbox(s, fx, Emu(cy + Inches(0.6)), Inches(3.35), Inches(0.9), "（関数）\n中でどう処理するかは\n呼び出す側は知らなくてよい",
        size=11.5, color=SUB, align=PP_ALIGN.CENTER, font=FONT_SANS, line_spacing=1.2)

arrow(s, Inches(8.45), Emu(cy + Inches(0.15)), Inches(1.15), Inches(0.4))
textbox(s, Inches(8.3), Emu(cy - Inches(0.32)), Inches(1.5), Inches(0.35), "住所データを返す",
        size=10.5, color=SUB, align=PP_ALIGN.CENTER, font=FONT_SANS)

# 結果
card(s, Inches(9.75), cy, Inches(3.0), ch, fill=SURFACE2)
textbox(s, Inches(9.75), Emu(cy + Inches(0.15)), Inches(3.0), Inches(0.4), "画面", size=14, bold=True,
        color=INK, align=PP_ALIGN.CENTER, font=FONT_SANS)
textbox(s, Inches(9.75), Emu(cy + Inches(0.6)), Inches(3.0), Inches(0.9), "住所が\n自動入力される",
        size=12.5, color=SUB, align=PP_ALIGN.CENTER, font=FONT_SANS, line_spacing=1.2)

box = card(s, Inches(0.85), Inches(5.1), Inches(11.6), Inches(1.15), fill=SURFACE)
textbox(s, Inches(1.15), Inches(5.35), Inches(11.0), Inches(0.7),
        "「機能をひとつのまとまりとして作っておき、必要なときに呼び出す」——これが関数の考え方。\n機能をつくることも、プログラマーの大事な仕事の一つです。",
        13.5, color=INK, font=FONT_SANS, line_spacing=1.35)

footer(s, 9)

# ============================================================
# スライド 10: コメント投稿を有効化しよう（1章の続き、実演の締め）
# — 実際は STEP1 スライド7で扱うため、ここではデータベースSTEP3へ
# ============================================================
s = add_slide()
set_bg(s)
kicker(s, "STEP 3")
title(s, "データベースって何？")
textbox(s, Inches(0.5), Inches(1.35), Inches(11), Inches(0.5),
        "投稿やコメントの情報は、どうやって保存されているんだろう？",
        size=14, color=SUB, font=FONT_SANS)

# 左：説明
card(s, Inches(0.5), Inches(2.05), Inches(5.6), Inches(4.5))
textbox(s, Inches(0.85), Inches(2.3), Inches(5.0), Inches(0.4),
        "考えてみよう", size=14, bold=True, color=MAIN, font=FONT_SANS)
textbox(s, Inches(0.85), Inches(2.75), Inches(5.0), Inches(0.6),
        "「コメント」を特定するには、どんな情報が必要そう？",
        size=13.5, color=INK, font=FONT_SANS, line_spacing=1.3)

bullet_block(s, Inches(0.85), Inches(3.55), Inches(5.0), Inches(2.7),
             ["データを整理して扱いやすくすることも\nプログラマーの大事な仕事", "タグ検索も、データベースへの問い合わせ（SQL）で\n欲しい情報だけを取り出している", "AI時代になるほど、この考え方はますます重要"],
             size=13)

# 右：コメントに必要な情報カード
rx = Inches(6.4)
card(s, rx, Inches(2.05), Inches(6.4), Inches(4.5), fill=SURFACE2)
textbox(s, Emu(rx + Inches(0.35)), Inches(2.3), Inches(5.7), Inches(0.4),
        "コメントに必要な情報", size=14, bold=True, color=INK, font=FONT_SANS)

fields = ["コメントID", "投稿されたスポット", "投稿日時", "コメント本文", "投稿したユーザー"]
fy = Inches(2.85)
for f in fields:
    tag_badge(s, Emu(rx + Inches(0.35)), fy, Inches(5.7), Inches(0.6), f)
    fy = Emu(fy + Inches(0.72))

footer(s, 10)

# ============================================================
# スライド 11: 入力チェックを追加しよう
# ============================================================
s = add_slide()
set_bg(s)
kicker(s, "STEP 4")
title(s, "入力チェックを追加しよう")
textbox(s, Inches(0.5), Inches(1.35), Inches(11), Inches(0.5),
        "コメント欄が空白のままでも、投稿できてしまう…",
        size=14, color=SUB, font=FONT_SANS)

# 現状カード
lx = Inches(0.5)
card(s, lx, Inches(2.1), Inches(5.55), Inches(2.1), fill=RGBColor(0xF7, 0xE9, 0xDC), line=MAIN)
textbox(s, Emu(lx + Inches(0.3)), Inches(2.3), Inches(5.0), Inches(0.4), "😮 今の状態",
        size=15, bold=True, color=MAIN, font=FONT_SANS)
bullet_block(s, Emu(lx + Inches(0.3)), Inches(2.85), Inches(4.9), Inches(1.3),
             ["空欄のまま投稿ボタンを押しても通ってしまう", "実際のシステムでは入力漏れチェックが必須"], size=13)

# 対応方針カード
rx = Inches(6.28)
card(s, rx, Inches(2.1), Inches(5.55), Inches(2.1))
textbox(s, Emu(rx + Inches(0.3)), Inches(2.3), Inches(5.0), Inches(0.4), "✅ やること",
        size=15, bold=True, color=INK, font=FONT_SANS)
bullet_block(s, Emu(rx + Inches(0.3)), Inches(2.85), Inches(4.9), Inches(1.3),
             ["comments.php に短いコードを追加", "if文で「空文字かどうか」をチェックする"], size=13)

codebox = rect(s, Inches(0.5), Inches(4.5), Inches(11.3), Inches(1.55), fill=INK, radius=0.06)
tf = codebox.text_frame
tf.word_wrap = True
tf.margin_left = Inches(0.3); tf.margin_top = Inches(0.25)
p1 = tf.paragraphs[0]; p1.line_spacing = 1.4
r1 = p1.add_run(); r1.text = "if ($message === '') {"
r1.font.name = "Courier New"; r1.font.size = Pt(15); r1.font.color.rgb = RGBColor(0xF7, 0xE9, 0xDC)
p2 = tf.add_paragraph(); p2.line_spacing = 1.4
r2 = p2.add_run(); r2.text = '    // コメントが空のときは投稿を止める'
r2.font.name = "Courier New"; r2.font.size = Pt(14); r2.font.color.rgb = RGBColor(0x9A, 0x9A, 0x8A)
p3 = tf.add_paragraph(); p3.line_spacing = 1.4
r3 = p3.add_run(); r3.text = "}"
r3.font.name = "Courier New"; r3.font.size = Pt(15); r3.font.color.rgb = RGBColor(0xF7, 0xE9, 0xDC)

textbox(s, Inches(0.5), Inches(6.28), Inches(11.3), Inches(0.5),
        "難しそうに見えて、実は実際のシステムでもよく使われる基本的な機能です。",
        size=12.5, color=SUB, font=FONT_SANS, align=PP_ALIGN.CENTER)

footer(s, 11)

# ============================================================
# スライド 12: アレンジ課題
# ============================================================
s = add_slide()
set_bg(s)
kicker(s, "STEP 5")
title(s, "自由にアレンジしてみよう")
textbox(s, Inches(0.5), Inches(1.35), Inches(11), Inches(0.5),
        "「言われた通り」じゃなく、自分で考えて直してみよう（任意・発展）",
        size=14, color=SUB, font=FONT_SANS)

card(s, Inches(0.5), Inches(2.15), Inches(11.3), Inches(2.0))
textbox(s, Inches(0.85), Inches(2.4), Inches(10.6), Inches(0.4),
        "例：コメントの表示を変えてみる", size=15, bold=True, color=MAIN, font=FONT_SANS)

bx, by, bw, bh = Inches(1.0), Inches(3.0), Inches(4.0), Inches(0.9)
pill(s, bx, by, bw, bh, fill=SURFACE2)
textbox(s, bx, by, bw, bh, "「ジョンさん」", size=15, color=INK, align=PP_ALIGN.CENTER,
        anchor=MSO_ANCHOR.MIDDLE, font=FONT_SANS)
arrow(s, Inches(5.2), Emu(by + Inches(0.2)), Inches(1.0), Inches(0.5), fill=MAIN)
pill(s, Inches(6.4), by, bw, bh, fill=RGBColor(0xF7, 0xE9, 0xDC), line=MAIN)
textbox(s, Inches(6.4), by, bw, bh, "「ジョン様」", size=15, color=MAIN, bold=True, align=PP_ALIGN.CENTER,
        anchor=MSO_ANCHOR.MIDDLE, font=FONT_SANS)

bullet_block(s, Inches(0.85), Inches(4.6), Inches(10.6), Inches(1.4),
             ["タグの表示を変えてみる", "コメント表示の文言を工夫してみる（「さん」→「様」など）", "他にも気になった部分は自由に触ってみよう"],
             size=14)

footer(s, 12)

# ============================================================
# スライド 13: 体験の流れ（振り返り）
# ============================================================
s = add_slide()
set_bg(s)
kicker(s, "REVIEW")
title(s, "今日たどった道のり")

flow = [
    "プログラミングと Web の仕組みを知る",
    "サイトを起動して画面を確認",
    "ボタンの文字を直して“味見”",
    "会員登録してログイン",
    "現在地から住所を自動入力（API体験）",
    "コメント投稿を有効化",
    "データベースの考え方を知る",
    "入力チェックを自分で追加",
]
col_n = 2
row_n = 4
col_w = Inches(5.55)
row_h = Inches(1.0)
gap_x = Inches(0.2)
gap_y = Inches(0.12)
start_x = Inches(0.5)
start_y = Inches(1.85)

for i, f in enumerate(flow):
    col = i // row_n
    row = i % row_n
    x = Emu(start_x + col * (col_w + gap_x))
    y = Emu(start_y + row * (row_h + gap_y))
    card(s, x, y, col_w, row_h)
    step_badge(s, Emu(x + Inches(0.22)), Emu(y + Inches(0.29)), i + 1, "")
    textbox(s, Emu(x + Inches(0.85)), y, Emu(col_w - Inches(1.1)), row_h,
            f, size=13.5, color=INK, font=FONT_SANS, anchor=MSO_ANCHOR.MIDDLE, line_spacing=1.15)

footer(s, 13)

# ============================================================
# スライド 14: まとめ
# ============================================================
s = add_slide()
set_bg(s)
kicker(s, "SUMMARY")
title(s, "今日の体験、実は仕事そのもの")

pairs = [
    ("ボタンの文字を変える", "→ UIの改善"),
    ("住所を自動入力する", "→ 外部API連携・データ処理"),
    ("コメントを投稿できるようにする", "→ 機能の実装"),
    ("空欄チェックを追加する", "→ バリデーション（入力チェック）"),
    ("表示をアレンジする", "→ 使いやすさ・見た目の工夫"),
]
y = Inches(1.95)
for left, right in pairs:
    card(s, Inches(0.5), y, Inches(11.3), Inches(0.75))
    textbox(s, Inches(0.85), y, Inches(6.3), Inches(0.75), left, size=14, color=INK,
            anchor=MSO_ANCHOR.MIDDLE, font=FONT_SANS)
    textbox(s, Inches(7.3), y, Inches(4.3), Inches(0.75), right, size=14, color=MAIN, bold=True,
            anchor=MSO_ANCHOR.MIDDLE, font=FONT_SANS)
    y = Emu(y + Inches(0.85))

textbox(s, Inches(0.5), Inches(6.35), Inches(11.3), Inches(0.5),
        "小さな修正をして、動かして、確認する。この繰り返しが、実際の開発でも基本の流れです。",
        13.5, color=SUB, font=FONT_SANS, align=PP_ALIGN.CENTER)

footer(s, 14)

# ============================================================
# スライド 15: 締め
# ============================================================
s = add_slide()
set_bg(s, BG)
c1 = s.shapes.add_shape(MSO_SHAPE.OVAL, Inches(-2.2), Inches(4.5), Inches(5), Inches(5))
c1.fill.solid(); c1.fill.fore_color.rgb = RGBColor(0xEF, 0xE6, 0xD3)
c1.line.fill.background(); c1.shadow.inherit = False

c2 = s.shapes.add_shape(MSO_SHAPE.OVAL, Inches(10.5), Inches(-2), Inches(4.5), Inches(4.5))
c2.fill.solid(); c2.fill.fore_color.rgb = RGBColor(0xF7, 0xE9, 0xDC)
c2.line.fill.background(); c2.shadow.inherit = False

brand_dot(s, Inches(5.87), Inches(1.6), Inches(0.55))

textbox(s, Inches(1.0), Inches(2.75), Inches(11.33), Inches(1.0),
        "小さな修正の積み重ねが、", size=28, color=INK, bold=True, align=PP_ALIGN.CENTER, font=FONT_SERIF)
textbox(s, Inches(1.0), Inches(3.4), Inches(11.33), Inches(1.0),
        "Webアプリを動かす力になる。", size=28, color=MAIN, bold=True, align=PP_ALIGN.CENTER, font=FONT_SERIF)

line = rect(s, Inches(5.87), Inches(4.5), Inches(1.6), Pt(3), fill=MAIN)

textbox(s, Inches(1.0), Inches(4.85), Inches(11.33), Inches(0.6),
        "ご参加ありがとうございました", size=16, color=SUB, align=PP_ALIGN.CENTER, font=FONT_SANS)

footer(s, 15)

out_path = "/Users/shoyabushita/Desktop/web_taiken/Webプログラミング体験_資料.pptx"
prs.save(out_path)
print("saved:", out_path, "slides:", len(prs.slides))
