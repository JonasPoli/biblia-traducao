
# Louw-Nida
Vamos implantar no sistema a análise do Louw-Nida.


# Campo Semântico
Analise o documento docs/louw_nida_completo_pt_br_final.csv
Crie uma entidade nova para armazenar os dados do campo semantico
a primeira coluna, ID_LouwNida, possui um dois numeros separados por "." ponto, o primeiro numero é o dominio e o segundo é o subdominio.


# OpenGNT
Para isso, vamos criar uma grande tabela com as informações do Louw-Nida.
Você vai ler a planilha docs/OpenGNT_version3_3.csv e vai extrair não só os campos, as as diversas partes dos campos cada uma em um campo próprio.

Vai criar uma entidade chamada LouwNida que vai receber as informações do arquivo OpenGNT_version3_3.csv.

Por exemplo, a coluna 〔Book｜Chapter｜Verse〕na nova entidade será quebrada em 3 campos: book, chapter e verse.

Segue a explicação do arquivo OpenGNT.

## **1\) `OGNTsort`**

**Para que serve:**  
 É o **ID de ordenação** do token no OpenGNT.

* É um número sequencial (com zeros à esquerda) que indica a **posição exata** da palavra no texto.

* Serve como **chave estável** para ordenar e para “apontar” uma palavra específica sem depender do versículo.

✅ Ex.: `000125` \= 125ª palavra do texto OpenGNT.

---

## **2\) `TANTTsort`**

**Para que serve:**  
 É a posição equivalente do token segundo o projeto **TANTT** (um texto-alvo/estrutura de referência usada para alinhamento).

* Ajuda quando você quer cruzar OpenGNT com bases que seguem a ordenação TANTT.

* Nem sempre é idêntico ao OGNTsort, porque projetos podem divergir em tokenização/ordenação em casos específicos.

---

## **3\) `FEATURESsort1`**

**Para que serve:**  
 É a **chave de ligação** (join) com a outra planilha:

* `OpenGNT_keyedFeatures.csv`

Ou seja:

* `OpenGNT_version3_3.FEATURESsort1` \= `OpenGNT_keyedFeatures.FEATURESsort1`

✅ Com isso você puxa metadados discursivos (cláusulas, discurso relatado, citações etc.) quando precisar.

---

## **4\) `LevinsohnClauseID`**

**Para que serve:**  
 Identifica a **cláusula** (unidade de sentido/sintaxe/discurso) segundo a análise discursiva associada a Levinsohn.

* Ex.: `c1`, `c2`, etc.

* Várias palavras compartilham o mesmo ClauseID.

* Útil para análises do tipo: “o que acontece dentro de uma mesma cláusula?” / “marcadores discursivos por cláusula”.

---

## **5\) `OTquotation`**

**Para que serve:**  
 Marca se o token está dentro de **citação do Antigo Testamento**.

* Normalmente aparece como `-` quando não é citação.

* Quando marcado, ajuda a filtrar “palavras em trechos de citação” vs “texto do autor”.

---

# **6\) `〔BGBsortI｜LTsortI｜STsortI〕`**

Esta coluna é “composta” e traz **três ordenações alternativas** do token.

### **6.1) `BGBsortI`**

Ordenação conforme um “texto base” (BGB) usado como referência editorial.

### **6.2) `LTsortI` (Long Text sort index)**

Ordenação conforme a tradição/edição “Long Text”.

### **6.3) `STsortI` (Short Text sort index)**

Ordenação conforme a tradição/edição “Short Text”.

**Por que isso existe:**  
 Às vezes o mesmo conteúdo pode ter variações de tradição textual, e esses índices ajudam a manter **alinhamento** quando a ordem/tokenização muda.

---

# **7\) `〔Book｜Chapter｜Verse〕`**

Localização bíblica canônica.

### **7.1) `Book`**
Deve ser chave com a tabela de livros.
Número do livro (código numérico).

* Ex.: `40` costuma ser Mateus.

### **7.2) `Chapter`**

Capítulo (número normal).

### **7.3) `Verse`**
Deve ser chave com a tabela de versículos biblia_verse_ext
Versículo (número normal).

**Por que isso existe se já tem `OGNTsort`:**  
 Porque `OGNTsort` é uma posição global no texto inteiro, e `Book/Chapter/Verse` te dá a referência bíblica “humana”.

---

# **8\) `〔OGNTk｜OGNTu｜OGNTa｜lexeme｜rmac｜sn〕`**

Aqui ficam as informações centrais “linguísticas” do token.

### **8.1) `OGNTk`**

Forma “normalizada” (uma grafia padrão interna do OpenGNT).

### **8.2) `OGNTu`**

Forma em **Unicode**, preservando os caracteres gregos (e diacríticos conforme usado no dataset).

### **8.3) `OGNTa`**

Forma alternativa/ASCII (ou uma forma “simplificada” para sistemas que não lidam bem com Unicode).  
 *Na prática, é útil para buscas e integrações legadas.*

### **8.4) `lexeme`**

O **lema** (forma de dicionário).  
 Ex.: um verbo conjugado aparece no texto como forma flexionada, mas aqui você tem o “verbo base”.

### **8.5) `rmac`**

Código morfológico (padrão do tipo “Robinson Morphological Analysis Codes”).

* Ex.: `N-NSF` costuma significar algo como:

  * N \= substantivo

  * Nominativo

  * Singular

  * Feminino  
     (Exato varia conforme a convenção, mas a ideia é “classe \+ caso \+ número \+ gênero”, etc.)

### **8.6) `sn`**

“Strong Number” (Strong’s).  
 No seu arquivo aparece como `Gxxxx` (ex.: `G2424`).

* **G** \= Greek (grego)

* número \= ID do vocábulo no sistema Strong

---

# **9\) `〔BDAGentry｜EDNTentry｜MounceEntry｜GoodrickKohlenbergerNumbers｜LN-LouwNidaNumbers〕`**

Este bloco é “lexical/enciclopédico”: aponta para dicionários e sistemas de indexação.

### **9.1) `BDAGentry`**

Entrada correspondente no **BDAG** (léxico acadêmico grego).

### **9.2) `EDNTentry`**

Entrada no **EDNT** (outro léxico acadêmico do NT).

### **9.3) `MounceEntry`**

Entrada no léxico de **Mounce** (muito usado em estudos bíblicos).

### **9.4) `GoodrickKohlenbergerNumbers`**

Numeração Goodrick–Kohlenberger (GK), um outro sistema de indexação lexical.

### **9.5) `LN-LouwNidaNumbers`**

Aqui aparece o **domínio semântico** (Louw-Nida), no formato típico:

**`LN-<domínio>.<subdomínio>`**  
 ou às vezes com múltiplos:  
 **`LN-10.24，33.19`** (separados por vírgula chinesa `，` no seu arquivo)

Agora, do jeitinho que você pediu, “quebrando em partes”:

* **Parte 1:** `LN`

  * Letras que indicam o **sistema Louw-Nida** (LouwNida).

* **Parte 2:** `-` (hífen)

  * Separador, indicando “prefixo do sistema” → “código do domínio”.

* **Parte 3:** `33` (primeiro número)

  * **Domínio semântico principal** (top-level).

* **Parte 4:** `.` (ponto)

  * Separador hierárquico dentro do sistema LN.

* **Parte 5:** `38` (segundo número)

  * **Subdomínio semântico** dentro do domínio principal.
  Deve ser chave com o numero do domínio.

✅ Exemplo real (do seu arquivo):  
 `LN-33.38` \= domínio 33, subdomínio 38\.

📌 Observação crucial:  
 Esse campo te dá **o número do domínio**, não necessariamente o “nome do domínio”. O nome vem de uma tabela/obra LN.

---

# **10\) `〔transSBLcap｜transSBL｜modernGreek｜Fonética_Transliteración〕`**

Este bloco foca em transliteração e leitura.

### **10.1) `transSBLcap`**

Transliteração padrão SBL com capitalização (útil para começo de frase/nomes próprios).

### **10.2) `transSBL`**

Transliteração SBL “normal”.

### **10.3) `modernGreek`**

Forma aproximada em grego moderno (quando disponível/útil).

### **10.4) `Fonética_Transliteración`**

Uma transliteração/fonética voltada à pronúncia (para leitura), geralmente mais “fala” do que “forma acadêmica”.

---

# **11\) `〔TBESG｜IT｜LT｜ST｜Español〕`**

Traduções/glosas em línguas diferentes (ou versões textuais).

### **11.1) `TBESG`**

Uma glosa/tradução associada ao conjunto TBESG.

### **11.2) `IT`**

Italiano.

### **11.3) `LT`**

Tradução/versão vinculada ao “Long Text”.

### **11.4) `ST`**

Tradução/versão vinculada ao “Short Text”.

### **11.5) `Español`**

Espanhol.

📌 Importante:  
 Isso não é “tradução bíblica completa”, e sim **glosas/segmentos** alinhados ao token.

---

# **12\) `〔PMpWord｜PMfWord〕`**

Campos relacionados a formas **masculinas/femininas** (quando aplicável).

### **12.1) `PMpWord`**

Forma masculina (p \= “masc/” em algumas convenções internas).

### **12.2) `PMfWord`**

Forma feminina.

Nem toda palavra terá isso preenchido; é mais relevante para termos que variam por gênero.

---

# **13\) `〔Note｜Mvar｜Mlexeme｜Mrmac｜Msn｜MTBESG〕`**

Este bloco guarda **correções editoriais/variações manuais** (o “M” é um forte indicativo de “Manual/Modified/Markup”).

### **13.1) `Note`**

Notas editoriais (observações sobre aquela linha/token).

### **13.2) `Mvar`**

Uma marca de “variante” (quando há variação textual/morfológica sinalizada).

### **13.3) `Mlexeme`**

Lema alternativo/corrigido manualmente (quando o padrão automático não serve).

### **13.4) `Mrmac`**

Código morfológico alternativo/corrigido.

### **13.5) `Msn`**

Strong alternativo/corrigido.

### **13.6) `MTBESG`**

Glosa/tradução TBESG alternativa/corrigida.

# Ajustes no sistema existente

Depois de criar a entidade LouwNida, vamos ajustar o sistema existente para que ele use essa nova entidade.

## Ajuda na tradução
Nesta URL, https://127.0.0.1:8000/admin/translation/40/1/1
quando passar o mouse por cima de uma palavra e aparecer a ajuda, acrecente a referência Louw-nida dessa palavra, assim:
Low nida: LN-33.38  domínio semântico 33 (Comunicação) subdomínio 38 (declaração escrita, certificado, aviso, registro)

## Explicação da palvra
Em https://127.0.0.1:8000/api/word-detail/G1078?term=da%20genealogia&pt_type=Substantivo%20-%20Genitivo%20Feminino%20Singular
acrecente a referência Louw-nida dessa palavra, assim:
Low nida: LN-33.38  domínio semântico 33 (Comunicação) subdomínio 38 (declaração escrita, certificado, aviso, registro)


