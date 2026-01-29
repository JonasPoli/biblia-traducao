# Biblia interlinear
crie um command novo que cria a biblia interlinear.

Este command deve criar um arquivo .md chamado biblia-interlinear.html

ele deve supor que cada livro é o o primeiro indice:
`<h1>Genesis</h1>`

Os capítulos devem começar com
`<h2>Genesis - Capítulo 1</h2>`

Depois, no terceiro nível, teremos o texto do versículo.



## Velho Testamento

O loop principal deve ser da tabela `verse`
No velho testamento, o 'título de nivel 3' deve 
O texto do versículo deve ser retirado de verse_world

O titulo, nivel 3 deve ser contruído da seguinte forma:
localize todos os registros de verse.id em verse_world.verse_id, ordenado por position.
Depois, deve mostrar a tradução word_portuguese e word_original, sendo que a word_original deve estar entre parenteses e em itálico, assim 

`<h3> No princípio <i>(רֵאשִׁית)</i> , criou <i>(בָּרָא אֵת)</i> Deus <i>(אֱלֹהִים)</i> os céus <i>(שָׁמַיִם)</i> e <i>(אֵת)</i> a terra <i>(אֶרֶץ)</i></h3>`

dentro de `###` deve ser contruido um mapa de cada uma das palavras:

<h4> רֵאשִׁית </h4>
Ou seja, o titulo 4 será a palavra word_original em negrito.

Abaixo, para explicar o conteúdo da palavra, deve mostrar a tradução
<p>Strong: <strong>verse_world.strong_code</strong></p>
<p>word_portuguese, espaço hifém espaço, a  verse_world.tranliteration, espaço hifém espaço,   verse_world.portuguese_type.</p>
Depois, deve-se localizar o  verse_world.strong_definition_id em strong_definition.
Na linha de baixo, deve apresentar o <p>strong_definition.definition</p> e o   <p>strong_definition.full_definition</p>. 

## Novo Testamento

O loop principal deve ser da tabela `louw_nida`, localizando book, chapter e verse para saber onde está, quando mudou de capítulo ou livro.


O titulo, nivel 3 deve ser contruído da seguinte forma:
localize todos os registros do versículo dentro de louw-nida filtrado pelo versículo atual (book, chapter e verse), ordenado por ognt_sort exibindo o conteudo de ognt_a.


`<h3>Βίβλος γενέσεως Ἰησοῦ Χριστοῦ υἱοῦ Δαυὶδ* υἱοῦ Ἀβραάμ</h3>`


dentro de `###` deve ser contruido um mapa de cada uma das palavras:

<h4>Βίβλος</h4>
Ou seja, o titulo 4 será a palavra ognt_a em negrito.


com o louw_nida.sn deve ser localizado o registro em strong_definition.code
Se encontrado, 
<p>Strong: <strong>strong_definition.code</strong></p>
<p>strong_definition.transliteration - strong_definition.pronunciation</p>

Abaixo, para explicar o conteúdo da palavra, deve mostrar a tradução, deve pegar o sn e o ln_number e procurar em louw_nida_sense os registros que contenham o sn e o ln_number.
louw_nida_sense.low_nida_number = louw_nida.ln_number
louw_nida_sense.strong_code = louw_nida.sn
daqui deve ser mostrado o low_nida_sense.ideia_central_pt_br e o sentido_semantico_pt_br

