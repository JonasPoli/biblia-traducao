# **✅ Regra principal**

**Todo o texto bíblico (corpo de texto) é justificado nas duas colunas.**

Isso vale para:

* texto dos versículos

* títulos longos (exceto títulos muito curtos)

* notas de rodapé

* referências cruzadas

---

# **🧩 Mas há detalhes importantes para que a justificação fique perfeita**

## **▶ 1\. Evitar “rios de texto”**

Em duas colunas estreitas, justificar SEM ajustes gera espaços exagerados entre palavras.

Por isso, editores usam:

* **hifenização automática**

* **espaçamento de palavra com limites mínimos e máximos**

* **text-align: justify; text-justify: inter-word;**

* Para PDFs: **controle profissional no InDesign/LaTeX**.

Se você estiver fazendo em HTML, é recomendado:

`p {`  
  `text-align: justify;`  
  `hyphens: auto;`  
`}`

---

## **▶ 2\. O número do versículo não deve quebrar o alinhamento**

Exemplo do alinhamento correto:

  `1 No princípio criou Deus os céus e a terra.`  
   `2 E a terra era sem forma e vazia; e havia`  
     `trevas sobre a face do abismo...`

Observe que o número:

* ocupa seu próprio espaço

* mas o texto à direita continua perfeitamente alinhado

* se o número for sobrescrito, o alinhamento não é afetado

---

## **▶ 3\. Versículos em nova linha \= melhor justificação**

Como cada versículo começa na margem esquerda, o navegador/diagramador tem mais liberdade para justificar sem criar falhas.

Por isso seu layout facilita muito a leitura.

---

## **▶ 4\. Títulos não são justificados**

Os títulos (assuntos):

* **ficam alinhados à esquerda**

* **em itálico**

* **com fonte maior**

Porque justificar um título curto gera espaços feios:

❌ *Assunto Importante do Capítulo*  
 → ficaria cheio de buracos

✔ *Assunto Importante do Capítulo*  
 → alinhado à esquerda (ou ao centro em algumas versões)

---

## **▶ 5\. Rodapé das referências também é justificado**

Mas com uma largura maior que a coluna principal:

`¹ 1:3  – Deus cria a luz em contraste com o caos primordial.`  
`² 1:5  – Ciclo dia/noite estabelecido como ordem da criação.`

---

# **📌 Resumo final**

Sim, **o texto deve ser justificado**, e a forma ideal é:

* Corpo bíblico → **JUSTIFICADO**

* Versículos → nova linha e justificados

* Títulos → **não justificados**, alinhados à esquerda

* Rodapé → **justificado**

* Hifenização → **ativada**, para evitar espaços exagerados

---

Se quiser, posso gerar:

📄 **Um exemplo visual pronto (em HTML ou imagem)**  
 🎨 **Um CSS completo para sua diagramação**  
 📘 **Um PDF simulado de uma página bíblica com justificação perfeita**

Deseja um exemplo renderizado?

##### **Você disse:**

crie completamente as diretrizes de como deve ser para gerar em Latex

###### **O ChatGPT disse:**

Pensou por 34s

Beleza, vamos transformar esse layout em “regras oficiais” de LaTeX 😊  
 Vou te dar diretrizes **conceituais** \+ um **modelo de preâmbulo e comandos** que você pode adaptar.

---

## **1\. Motor de compilação**

Para usar **Times New Roman** e ter bom controle tipográfico:

* Preferencial: **XeLaTeX** ou **LuaLaTeX**

  * Permite usar `fontspec` e a fonte do sistema (Times New Roman).

* Alternativa com pdfLaTeX:

  * Usar os pacotes `newtxtext` / `newtxmath` para uma serif parecida.

---

## **2\. Estrutura básica do documento**

### **2.1. Classe e página**

Diretrizes:

* Duas colunas fixas.

* Margens estreitas (bíblias costumam ser compactas).

* Linha vertical entre as colunas.

Exemplo de base:

`\documentclass[12pt,twocolumn]{article} % ou book, se for obra completa`

`\usepackage[a5paper,margin=1.5cm]{geometry} % ajuste de tamanho/margem`  
`\setlength{\columnsep}{0.7cm}               % espaço entre colunas`  
`\setlength{\columnseprule}{0.4pt}          % linha vertical entre colunas`

### **2.2. Fonte, idioma e justificação**

`% Compilar com XeLaTeX ou LuaLaTeX`  
`\usepackage{fontspec}`  
`\setmainfont{Times New Roman}`

`\usepackage[brazil]{babel}`  
`\usepackage{microtype} % melhora a justificação`

`% Hifenização e justificação`  
`\pretolerance=1000`  
`\tolerance=2000`  
`\emergencystretch=2em`

Esses ajustes ajudam a evitar “buracos” no texto justificado.

---

## **3\. Regras de layout mapeadas para LaTeX**

### **3.1. Cabeçalho: nome do livro**

Regra:

Nome do livro em fonte bem grande no início, centralizado.

Diretriz LaTeX:

* Criar um comando `\Book{Gênesis}` que:

  * quebra página,

  * limpa contadores,

  * imprime o nome do livro.

`\newcommand{\Book}[1]{%`  
  `\clearpage`  
  `\setcounter{chapter}{0}`  
  `\setcounter{verse}{0}`  
  `\begin{center}`  
    `{\Huge\bfseries #1}`  
  `\end{center}`  
  `\bigskip`  
`}`

---

### **3.2. Capítulos com número grande (tipo Drop Cap)**

Regra:

Número do capítulo grande, ocupando altura de duas linhas.

Você pode:

* usar `lettrine` (mais bonito), ou

* fazer algo simples com `\Huge`.

Vamos usar um contador de capítulos e de versículos:

`\newcounter{chapter}`  
`\newcounter{verse}`  
`\newcounter{xref} % referências cruzadas`

Capítulo (sem título):

`\newcommand{\Chapter}{%`  
  `\par\bigskip`  
  `\stepcounter{chapter}%`  
  `\setcounter{verse}{0}%`  
  `\setcounter{xref}{0}%`  
  `% número grande do capítulo (Drop Cap simplificado)`  
  `{\Huge\bfseries \thechapter}\hspace{0.5em}`  
`}`

Se quiser capítulo \+ título de assunto:

`\newcommand{\ChapterWithTitle}[1]{%`  
  `\par\bigskip`  
  `\stepcounter{chapter}%`  
  `\setcounter{verse}{0}%`  
  `\setcounter{xref}{0}%`  
  `{\Huge\bfseries \thechapter}\par`  
  `{\large\itshape #1}\par\medskip`  
`}`

---

### **3.3. Títulos de assuntos**

Regra:

Títulos em itálico, um pouco maiores que o texto bíblico, não justificados.

Diretriz:

`\newcommand{\Assunto}[1]{%`  
  `\par\medskip`  
  `{\itshape\large #1}\par`  
  `\medskip`  
`}`

Como são títulos, mantenha alinhado à esquerda (sem justify manual).  
 O parágrafo seguinte volta a ser justificado normalmente.

---

### **3.4. Versículos (cada um em nova linha, v.1 sem número)**

Regras:

* Cada versículo em **nova linha**.

* Versículo 1 **sem número** visível.

* Versículos 2+ com número em negrito, menor e **antes do texto**.

Diretriz: criar um comando `\Verse{texto}` que:

1. incrementa o contador de versículo;

2. quebra linha;

3. imprime o número (se \> 1\) em negrito e pequeno.

Exemplo:

`\newcommand{\printversenum}{%`  
  `\ifnum\value{verse}>1`  
    `{\textbf{\scriptsize\theverse}\,}%`  
  `\fi`  
`}`

`\newcommand{\Verse}[1]{%`  
  `\par`  
  `\stepcounter{verse}%`  
  `\noindent`  
  `\printversenum`  
  `#1%`  
`}`

Uso:

`\Chapter`  
`\Assunto{A criação do mundo}`

`\Verse{No princípio, criou Deus os céus e a terra.}`  
`\Verse{A terra, porém, estava sem forma e vazia; havia trevas sobre a face do abismo, e o Espírito de Deus pairava por sobre as águas.}`  
`\Verse{Disse Deus: Haja luz; e houve luz.}`

---

## **4\. Referências cruzadas**

### **4.1. Regras que vamos implementar**

Do seu layout:

* Código numérico crescente, reiniciado em *cada capítulo* ⟶ usamos `\xref` com `\setcounter{xref}{0}` no `\Chapter`.

* Código aparece **antes** da palavra, em sobrescrito itálico.

* No rodapé:

  * código sobrescrito itálico;

  * `Capítulo:Versículo` em negrito;

  * texto da referência logo após.

### **4.2. Implementação básica com `\footnote`**

Diretriz:  
 Criar um comando com assinatura:

`\xref{cap:vers}{texto da referência}`

Ele deve:

* incrementar `xref`;

* imprimir o código sobrescrito e itálico no corpo do texto;

* criar uma nota de rodapé com o formato pedido.

`\newcommand{\xref}[2]{%`  
  `\stepcounter{xref}%`  
  `% Marca no texto`  
  `\textsuperscript{\textit{\thexref}}%`  
  `% Nota de rodapé`  
  `\footnote{%`  
    `\textsuperscript{\textit{\thexref}}\ %`  
    `\textbf{#1}\ %`  
    `#2%`  
  `}%`  
`}`

Uso dentro de um versículo:

`\Verse{No princípio, criou Deus\xref{1:1}{Ver também Jo 1:1 para o paralelo entre a criação e o Verbo eterno.}`  
`os céus e a terra.}`

LaTeX vai cuidar sozinho para que a nota fique no rodapé da **mesma página**.

🔎 **Obs. técnica**: em modo `twocolumn`, as notas ficam por coluna, não por página.  
 Se você quiser **rodapé em largura total** da página, pode considerar pacotes como `dblfnote`, `bigfoot` ou `reledmac`. As diretrizes gerais continuam as mesmas; só muda o pacote que gerencia as notas.

---

## **5\. Justificação, hifenização e aparência geral**

### **5.1. Texto justificado**

Por padrão, LaTeX já justifica. Com `microtype` e boas margens, você terá:

* texto justificado em ambas as colunas;

* hifenização automática em português (via `babel`).

Se quiser reforçar:

`\setlength{\parindent}{1em}   % recuo de parágrafo discreto`  
`\setlength{\parskip}{0pt}     % sem espaço extra entre parágrafos`

### **5.2. Versículos em nova linha**

Já garantimos isso com `\par` dentro de `\Verse`.

---

## **6. Esqueleto completo de exemplo (Versão Final)**

Aqui está o código LaTeX final que resolveu os problemas de duplicação e layout:

```latex
\documentclass[8pt,twocolumn]{article}

\usepackage[a5paper,margin=1cm]{geometry}
\setlength{\columnsep}{0.6cm}
\setlength{\columnseprule}{0.3pt}

\usepackage{iftex}
\ifPDFTeX
  \usepackage[T1]{fontenc}
  \usepackage[utf8]{inputenc}
  \usepackage{newtxtext}
  \PackageWarning{biblia}{Please use XeLaTeX or LuaLaTeX for proper Unicode support!}
\else
  \usepackage{fontspec}
  \setmainfont{Times New Roman}
  % Fallback fonts for missing glyphs (Greek/Hebrew)
  \newfontfamily\libertinefont{Linux Libertine O}
\fi

\usepackage[brazil]{babel}
\usepackage{microtype}

% Footnotes spanning full page width in twocolumn mode
\usepackage{dblfnote}
\interfootnotelinepenalty=10000

% Configure footnote spacing and limits
\setlength{\skip\footins}{1em}
\setlength{\footnotesep}{0.7em}
\dimen\footins=0.6\textheight

\pretolerance=1000
\tolerance=2000
\emergencystretch=2em

\setlength{\parindent}{0.8em}
\setlength{\parskip}{0pt}
\setlength{\baselineskip}{10pt}

\newcounter{chapter}
\newcounter{verse}
% Nota: Removemos o contador manual 'xref' e usamos o nativo 'footnote'

\newcommand{\Book}[1]{%
  \twocolumn[%
    \centering
    {\Huge\bfseries #1}
    \bigskip
    \bigskip
  ]%
  \setcounter{chapter}{0}%
  \setcounter{verse}{0}%
  \setcounter{footnote}{0}% Reset footnote counter per book
}

\newcommand{\Chapter}{%
  \par\bigskip
  \stepcounter{chapter}%
  \setcounter{verse}{0}%
  \setcounter{footnote}{0}% Reset footnote counter per chapter
  {\Huge\bfseries \thechapter}\hspace{0.5em}%
}

\newcommand{\Assunto}[1]{%
  \par\medskip
  {\itshape\large #1}\par
  \medskip
}

\newcommand{\printversenum}{%
  \ifnum\value{verse}>1
    {\textbf{\scriptsize\theverse}\,}%
  `\fi`
}

\newcommand{\Verse}[1]{%
  \par
  \stepcounter{verse}%
  \noindent
  \printversenum
  #1%
}

% Simplificado: Usa apenas o footnote nativo
% O LaTeX gerencia o número no texto e no rodapé automaticamente
\newcommand{\xref}[1]{%
  \footnote{#1}%
}

\begin{document}

\Book{Gênesis}

\Chapter

\Verse{No princípio, criou Deus\xref{Ver também Jo 1:1.} os céus e a terra.}

\end{document}
```

## **7. Lógica de Deduplicação (PHP)**

Para evitar referências duplicadas (ex: "Jesus" aparecendo 2x no rodapé do mesmo versículo), implementamos uma lógica **estrita** no `BibleController.php`:

1.  **Normalização**: Convertemos o termo para minúsculo e removemos espaços extras.
2.  **Rastreamento**: Mantemos um array `$seenTermsExact` para cada versículo.
3.  **Verificação**:
    *   Se o termo já foi visto (seja em referência específica ou global), ele é **ignorado**.
    *   Referências **Específicas** têm prioridade (são processadas primeiro).
    *   Referências **Globais** só entram se o termo ainda não foi "usado".

Isso garante que cada termo gere no máximo **uma** nota de rodapé por versículo, evitando a duplicação visual de números (ex: "44").