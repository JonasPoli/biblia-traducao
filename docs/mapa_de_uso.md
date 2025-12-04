# Mapa de uso

## Nova tabela
Deve ser criada uma nova tabela para armazenas as formas de se traduzir uma palavra na bíblia.
A tabela deve ser a 'Paradigm'
Esta tabela deve ter os campos:
Foreign_word: é a palavra extrangeira, em grego ou hebraico.
Translation: é a tradução da palavra em português.
StrongId: é o id da palavra no dicionário Strong.
RMAC: é o código gramatical, que é o código que indica a forma verbal (Imperativo Presente Ativo, neste caso: "saibam" / "conheçam"). Ou seja, $G5720$ não é uma palavra separada, mas sim uma informação técnica sobre a palavra $G1097$.
wordClass: é a classe da palavra, por exemplo, verbo, substantivo, adjetivo, etc.
Amount: vai conter a quantidade de vezes.

## Como montar os dados dessa tabela?
Você vai criar um command que, no primiro passo, apaga completamente a tabela.

Depois, vai adicionar os registros da seguinte maneira:
Você vai varrer todos os registros da verse_text filtrando por version_id = 22	
Ou seja, (ARAi) - 1993 - Almeida Revisada e Atualizada.

Para cada versículo você deve analisar da seguinte maneira:
Carregue o versículo na memória.
```
sabei<S>G1097</S> <n>γινώσκω</n><S>G1097</S> <S>G5720</S> que<S>G3754</S> <n>ὅτι</n><S>G3754</S> aquele que converte<S>G1994</S> <n>ἐπιστρέφω</n><S>G1994</S> <S>G5660</S> o pecador<S>G268</S> <n>ἀμαρτωλός</n><S>G268</S> do<S>G1537</S> <n>ἐκ</n><S>G1537</S> seu<S>G846</S> <n>αὐτός</n><S>G846</S> caminho<S>G3598</S> <n>ὁδός</n><S>G3598</S> errado<S>G4106</S> <n>πλάνη</n><S>G4106</S> salvará<S>G4982</S> <n>σώζω</n><S>G4982</S> <S>G5692</S> da<S>G1537</S> <n>ἐκ</n><S>G1537</S> morte<S>G2288</S> <n>θάνατος</n><S>G2288</S> a alma<S>G5590</S> <n>ψυχή</n><S>G5590</S> dele e<S>G2532</S> <n>καί</n><S>G2532</S> cobrirá<S>G2572</S> <n>καλύπτω</n><S>G2572</S> <S>G5692</S> multidão<S>G4128</S> <n>πλήθος</n><S>G4128</S> de pecados<S>G266</S> <n>ἀμαρτία</n><S>G266</S>.
```

Transforme este contúdo numa Array que deve conter
Palavra em Português: A tradução, antes de <S></S>
Strong ID (G): dentro da primeira ocorrencia de  <S></S>
Código Morfológico (RMAC): dentro de <n></n>
A palavra no idioma original, dentro de <n></n>


Exemplo:
A palavra em português é sabei.O Grego é $\gamma\iota\nu\omega\sigma\kappa\omega$ ($G1097$).A Strong ID $G1097$ aparece duas vezes.$G5720$ é um código gramatical que, em alguns sistemas de interlinear, indica a forma verbal (Imperativo Presente Ativo, neste caso: "saibam" / "conheçam"). Ou seja, $G5720$ não é uma palavra separada, mas sim uma informação técnica sobre a palavra $G1097$.
| Palavra em Português | Strong ID (G) | Código Morfológico (RMAC) | Palavra no idioma original |
| :---- | :---- | :---- | :---- |
| sabei | G1097 | G5720 | γινώσκω |
| que | G3754 | Não tem | ὅτι |
| converte | G1994 | G566 | ἐπιστρέφω |
| o pecador | G268 | Não tem | ἀμαρτωλός |
| do | G1537 | Não tem | ἐκ |

Ao montar essa array todas as palavras do campo 'Palavra em Português' devem conter todas as letras em maiúsculo, obedecendo inclusive os acentos e caracteres especiais.
As palavras do campo 'Palavra no idioma original', Strong ID (G), Código Morfológico (RMAC) e do campo 'Palavra em Português' devem ser aplicado um trim para remover espaços em branco no início e no fim.

Com base nesta array de dados criada, para cada linha dessa array, você deve procurar se existe, um registro em Paradigm que contenha a Strong ID, Palavra em Português e Código Morfológico (RMAC) identico à linha atual.
Se existir, você deve atualizar o registro incrementando o valor do campo Amount.
Caso não exista, você deve criar um novo registro com os valores da linha atual com o Amount = 1.

## servico de RMAC
Você deve criar um serviço neste sistema que contenha uma função que retorna a análise morfológica de uma palavra, com base  Código Morfológico (RMAC).

Com certeza\! Para criar um sistema que decodifica o RMAC, é essencial ter todas as tabelas de mapeamento em mãos, pois elas são a **espinha dorsal** do seu sistema de análise.

Abaixo está um texto completo, detalhando o funcionamento lógico do decodificador e fornecendo as **tabelas auxiliares completas** para o Grego do Novo Testamento, que é o foco do RMAC (Robinson's Morphological Analysis Codes).

### **🛠️ O Decodificador RMAC: Arquitetura e Lógica**

O sistema decodificador RMAC funciona dividindo o código de entrada em seus componentes (dígitos) e usando cada dígito como uma **chave de busca** em tabelas de referência pré-definidas. O resultado final é uma string concatenada que descreve a palavra.

#### **1\. Estrutura de Dados Base (As Tabelas de Mapeamento)**

O coração do sistema é uma coleção de tabelas (*Dicionários*, *Maps* ou *Arrays*) que mapeiam cada dígito a uma descrição gramatical. Estas tabelas devem ser as primeiras coisas a serem implementadas:

##### **Tabela A: 2ª Posição – Classe de Palavra (Parte do Discurso)**

| Dígito (Chave) | Classe da Palavra (Valor) |
| :---- | :---- |
| **1** | Adjetivo |
| **2** | Advérbio |
| **3** | Conjunção |
| **4** | Interjeição |
| **5** | Substantivo |
| **6** | Preposição |
| **7** | Artigo |
| **8** | **Verbo** |

##### **Tabela B: 3ª Posição – Flexão (Tempo ou Caso)**

| Dígito (Chave) | Se Classe \= Verbo (Tempo) | Se Classe \= Substantivo/Adjetivo (Caso) |
| :---- | :---- | :---- |
| **1** | Aoristo | Nominativo |
| **2** | Perfeito | Genitivo |
| **3** | Perfeito Futuro | Dativo |
| **4** | Futuro | Acusativo |
| **5** | Presente | Vocativo |
| **6** | Mais-que-Perfeito | *(Não Aplicável)* |

##### **Tabela C: 4ª Posição – Flexão (Modo ou Gênero)**

| Dígito (Chave) | Se Classe \= Verbo (Modo) | Se Classe \= Substantivo/Adjetivo (Gênero) |
| :---- | :---- | :---- |
| **1** | Indicativo | Masculino |
| **2** | Infinitivo | Feminino |
| **3** | Particípio | Neutro |
| **4** | Subjuntivo | *(Não Aplicável)* |
| **6** | Optativo | *(Não Aplicável)* |
| **7** | Imperativo | *(Não Aplicável)* |

##### **Tabela D: 5ª Posição – Flexão (Voz ou Número)**

| Dígito (Chave) | Se Classe \= Verbo (Voz) | Se Classe \= Substantivo/Adjetivo (Número) |
| :---- | :---- | :---- |
| **1** | Média | Singular |
| **2** | Ativa | Plural |
| **3** | Passiva | *(Não Aplicável)* |

##### **Tabela E: 6ª Posição – Flexão (Pessoa ou Grau)**

| Dígito (Chave) | Se Classe \= Verbo (Pessoa) | Se Classe \= Adjetivo (Grau) |
| :---- | :---- | :---- |
| **1** | 1ª Pessoa | Positivo |
| **2** | 2ª Pessoa | Comparativo |
| **3** | 3ª Pessoa | Superlativo |
| **0** | **2ª Pessoa Plural** (Usado em certas formas de Imperativo) | *(Não Aplicável)* |

##### **Tabela F: 7ª Posição – Flexão (Gênero para Particípios/Infinitivos)**

| Dígito (Chave) | Gênero |
| :---- | :---- |
| **1** | Masculino |
| **2** | Feminino |
| **3** | Neutro |

##### **Tabela G: 8ª Posição – Flexão (Número para Particípios/Infinitivos)**

| Dígito (Chave) | Número |
| :---- | :---- |
| **1** | Singular |
| **2** | Plural |

---

#### **2\. Lógica de Processamento da String RMAC**

A função de decodificação deve seguir estes passos:

##### **Passo 1: Recebimento e Validação da Entrada**

O sistema recebe o código (ex: "G5720").

* **Verificação:** Confirma que o primeiro caractere é **'G'** (Grego) e que o restante são dígitos.

##### **Passo 2: Determinação da Classe de Palavra (2ª Posição)**

O RMAC geralmente omite o dígito da 2ª posição se ele for **'8' (Verbo)**, encurtando o código. O sistema deve primeiro determinar o código da 2ª posição:

1. **Dígitos do Código:** Extrair a parte numérica (ex: "5720").  
2. **Lógica de Inferência:** Se o comprimento da parte numérica for **menor que 7** e os dígitos subsequentes (3ª, 4ª, 5ª posições) indicarem **Tempo, Modo ou Voz** (características exclusivas de verbos), o sistema **infere** que a 2ª posição é **8 (Verbo)**.  
3. **Resultado:** Busca na **Tabela A**. Se "G5720" for a entrada, o resultado é **"Verbo"**.

##### **Passo 3: Decodificação Sequencial com Regras Condicionais**

O sistema deve iterar sobre os dígitos restantes, do 3º ao 8º, aplicando regras condicionais para consultar a tabela correta:

1. **3ª Posição (Tempo/Caso):**  
   * **SE** a Classe (2ª Posição) for Verbo, use a coluna **Tempo** da **Tabela B** (Ex: 5 $\\rightarrow$ "Tempo Presente").  
   * **SE** a Classe for Substantivo/Adjetivo, use a coluna **Caso** da **Tabela B** (Ex: 2 $\\rightarrow$ "Caso Genitivo").  
2. **4ª Posição (Modo/Gênero):**  
   * **SE** a Classe for Verbo, use a coluna **Modo** da **Tabela C** (Ex: 7 $\\rightarrow$ "Modo Imperativo").  
   * **SE** a Classe for Substantivo/Adjetivo/Artigo, use a coluna **Gênero** da **Tabela C**.  
3. **5ª Posição (Voz/Número):**  
   * **SE** a Classe for Verbo, use a coluna **Voz** da **Tabela D** (Ex: 2 $\\rightarrow$ "Voz Ativa").  
   * **SE** a Classe for Substantivo/Adjetivo, use a coluna **Número** da **Tabela D**.  
4. **6ª Posição em Diante:** Estas posições só são consultadas se os códigos existirem e se a classe/modo for apropriado (por exemplo, a 7ª posição só é relevante se a 4ª posição for **3 (Particípio)**). Se um dígito for 0, ele geralmente representa omissão ou uma forma específica (como o 0 na 6ª posição para 2ª pessoa plural em alguns imperativos).

##### **Passo 4: Montagem da Saída**

Finalmente, o sistema encadeia todas as descrições em uma *string* legível, garantindo a ordem gramatical correta (Classe, Tempo, Modo, Voz, Pessoa, etc.) e ignorando os códigos que não se aplicam (ex: se não é Particípio, ignora-se a 7ª e 8ª posições).

**Exemplo final com G5720:**

1. Verbo  
2. Tempo Presente  
3. Modo Imperativo  
4. Voz Ativa  
5. 2ª Pessoa  
6. Plural (subentendido pelo 2ª pessoa e a forma do verbo).

**Saída final:** **Verbo, Presente, Imperativo, Ativo, 2ª Pessoa do Plural.**










## ajuste no sistema existente
neste local, https://127.0.0.1:8000/admin/translation/40/1/1
vamos fazer alguns ajustes 

as tabs com as palavras não devem já vir preenchida.
Serão como botões, que, ao ser clicado, carregão via uma API, a ser criada agora, os dados para apresentar informações silimares a isso:

````
<div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                                <div>
                                    <h3 class="text-2xl font-bold text-gray-900 mb-2">βίβλος</h3>
                                    <p class="text-lg text-gray-600 mb-4">bíblos</p>
                                    
                                                                            <div class="bg-gray-50 p-4 rounded border border-gray-200 mb-4">
                                            <h4 class="font-bold text-gray-700 mb-2">Strong: G976</h4>
                                            <div class="text-gray-800 mb-2">
                                                <strong>Definição:</strong> 
                                                <div class="mt-1"><p class="header"><nom>976</nom> <ell>βιβλος</ell> biblos </p><p>raíz primitiva; TDNT - 1:615,106; n n </p><ol class="c0"><li>um livro escrito, um registro, um rolo</li></ol></div>
                                            </div>
                                            <div class="text-gray-600 text-sm mt-2"></div>
                                            <div class="mt-4">
                                                <a href="https://search.nepebrasil.org/strongs/?id=G976" target="_blank" class="text-blue-600 hover:underline text-sm">
                                                    Ver no NEPE →
                                                </a>
                                            </div>
                                        </div>

                                        <button onclick="openAddGlobalModal('Livro', '\u03B2\u1F77\u03B2\u03BB\u03BF\u03C2', 'G976', 'b\u00EDblos', ``)" class="bg-purple-600 hover:bg-purple-700 text-white px-4 py-2 rounded transition-colors w-full flex items-center justify-center gap-2">
                                            <sl-icon name="plus-circle" aria-hidden="true" library="default"></sl-icon>
                                            Adicionar Referência Global
                                        </button>

                                        <button onclick="openAddSpecificModal('Livro', '\u03B2\u1F77\u03B2\u03BB\u03BF\u03C2', 'b\u00EDblos', ``)" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded transition-colors w-full flex items-center justify-center gap-2 mt-2">
                                            <sl-icon name="plus-circle" aria-hidden="true" library="default"></sl-icon>
                                            Adicionar Referência Específica
                                        </button>
                                                                    </div>

                                <div>
                                    <h4 class="font-bold text-gray-900 mb-4">Ocorrências</h4>
                                    <p class="text-gray-500 italic">Funcionalidade de ocorrências temporariamente indisponível na nova visualização.</p>
                                </div>
                            </div>
```                            
Nestas informações, onde está "Funcionalidade de ocorrências temporariamente indisponível na nova visualização." deve ser subtituúdo pelo seguinte:

Você vai procurar em Paradigm, todos os registros que contenham a Strong ID identico à linha atual.
Se existir, você deve apresentar a tabela de ocorrências, de forma bem clean.

Essa tabela deve listar RMAC, wordClass (se tiver) e o Amount.
Monte um gráfico de barras com essas informações laterais.