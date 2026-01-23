
# Louw-Nida

Vamos criar uma nova página para o Louw-Nida, que vai mostrar os domínios semânticos de uma palavra.

/louwnida/{word}

sendo que o {word} é o termo que vai ser buscado.
Pode ser LN-93.387 ou LN-93
ou seja com o domínio ou com o domínio e subdomínio.

## Dominios
Se for com o domínio, precisa listar todos os subdomínios.
Apresentar bem grande o nome do domínio e listar todos os subdomínios, apresentando cada subdomínio com seu Codigo completo, LN-93.387; sua ideia central e seu sentido semântico.
Cada um dos subdomínios listados deve ter um link para a página do subdomínio.

## Subdominios
Se for com o subdomínio, precisa apresentar a definição do dominio e do subdomínio.
Precisa apresentar, antes, o domínio a qual ela pertence.
Apresentar bem grande o nome do subdomínio e sua ideia central e seu sentido semântico.

Depois, precisa apresentar todas as ocorrências na bíblia que usam esse subdomínio.
Para isso, você deve pegar o {word} recebido como parâmetro e fazer uma busca em /Volumes/Dados/work/biblia-tradutor/src/Entity/LouwNida.php pelo campo ln_number.
Vão existir n ocorrências. Para cada ocorrência em LouwNida, você vai fazer o seguinte: 
1 - encontrar todas as ocorrências na prórpia LouwNida que tenham o mesmo book, chapter e verse, ordenando por ognt_sort, vamos chamar isso de palavras. Guarde numa variavel o strong (sn) e o ln_number (ln) da ocorrencia. (ocorrencia_sn e ocorrencia_ln)

### Traduções
A - Grego - para cada palavra, você vai fazer uma apresentação do campo ognt_a e quando encontrar uma palavra cujo ln_number for o mesmo do {word} recebido, você vai fazer marcá-la com negrito e uma borda colorida.
B - ingles - para cada palavra, você vai fazer uma apresentação do campo it e quando encontrar uma palavra cujo ln_number for o mesmo do {word} recebido, você vai fazer marcá-la com negrito e uma borda colorida.
C - espanhol - para cada palavra, você vai fazer uma apresentação do campo espanol e quando encontrar uma palavra cujo ln_number for o mesmo do {word} recebido, você vai fazer marcá-la com negrito e uma borda colorida.

Abaixo das 3 traduções do versículo, você vai procurar uma nova consulta no louw_nida_sense e mostrar os valores de strong code, ideia central e sentido semântico.


## Alterações
em https://127.0.0.1:8000/admin/translation/40/1/1
e em https://127.0.0.1:8000/api/word-detail/G2424?term=de%20Jesus&pt_type=Substantivo%20-%20Genitivo%20Masculino%20Singular&book=40&chapter=1&verse=1&sort=

quando mostrar o código LN (completo ou só o domínio), ele deve ser um link para este nova página.