# pptx資料の編集について

`Webプログラミング体験_資料.pptx`（体験授業本番用スライド）は `scripts/build_pptx.py` から生成している。

- スライドを直したいときは、`Webプログラミング体験_資料.pptx` を直接いじらず、`scripts/build_pptx.py` の該当箇所だけを `Edit` で差分修正してから `python3 scripts/build_pptx.py` を再実行する。
- スライド全体をゼロから作り直すと生成コストが大きいので避ける。既存スクリプトの再利用・差分編集を優先する。
- 生成されたpptxは `.gitignore` で無視されている（`*.pptx`）。
