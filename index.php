<!DOCTYPE html>
<html lang="it">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Chess WebApp Single Player – Gioca contro il Bot</title>
  
  <!-- Bootstrap CSS per design moderno e responsive -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
  <!-- Chessboard.js CSS per scacchiera e pezzi (caricati da CDN) -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/chessboard-js/1.0.0/chessboard-1.0.0.min.css">
  <link rel="stylesheet" href="style.css">

</head>
<body>
  <!-- HEADER -->
  <header>
    <h1>Chess WebApp Single Player</h1>
    <p>Gioca contro il Bot – Scegli il colore, il tempo e la difficoltà</p>
    <div id="gameStatus"></div>
  </header>
  
  <!-- OROLOGI -->
  <div id="clocks">
    <span id="clockWhite">Bianco: 05:00</span>
    <span id="clockBlack">Nero: 05:00</span>
  </div>
  
  <!-- BOARD -->
  <div id="board"></div>
  
  <!-- INFO PANELS: Catture e Registro Mosse -->
  <div id="infoPanels" class="container">
    <div class="row">
      <div class="col-md-6">
        <h5>Captured Pieces</h5>
        <div id="capturedPieces"></div>
      </div>
      <div class="col-md-6">
        <h5>Move History</h5>
        <div id="moveHistory"></div>
      </div>
    </div>
  </div>
  
  <!-- CONTROLS: Selezione Colore, Difficoltà (ELO) e Tempo di Partita, Undo e Reset -->
  <div id="controls" class="container">
    <div class="mb-3">
      <label for="playerColor" class="form-label">Scegli il tuo colore:</label>
      <select id="playerColor" class="form-select w-50 mx-auto">
        <option value="w" selected>Bianco</option>
        <option value="b">Nero</option>
      </select>
    </div>
    <div class="mb-3">
      <label for="botElo" class="form-label">Difficoltà del Bot (ELO):</label>
      <select id="botElo" class="form-select w-50 mx-auto">
        <option value="1200">1200 (Molto debole)</option>
        <option value="1400">1400</option>
        <option value="1600">1600</option>
        <option value="1800" selected>1800 (Medio)</option>
        <option value="2000">2000</option>
        <option value="2200">2200</option>
        <option value="2400">2400</option>
        <option value="2600">2600</option>
        <option value="2800">2800</option>
        <option value="3000">3000</option>
        <option value="3200">3200 (Massimo)</option>
      </select>
    </div>
    <div class="mb-3">
      <label for="gameTime" class="form-label">Tempo di Partita per Lato:</label>
      <select id="gameTime" class="form-select w-50 mx-auto">
        <option value="300" selected>5 minuti</option>
        <option value="600">10 minuti</option>
        <option value="900">15 minuti</option>
        <option value="1200">20 minuti</option>
      </select>
    </div>
    <div class="mb-3">
      <button id="undoMove" class="btn btn-warning me-2">Undo Mossa</button>
      <button id="resetGame" class="btn btn-secondary">Reset Partita</button>
    </div>
  </div>
  
  <!-- FOOTER -->
  <footer>
    <p>&copy; <?php echo date("Y"); ?> Chess WebApp di Bocaletto Luca</p>
  </footer>
  
  <!-- LIBRERIE JS -->
  <!-- jQuery (richiesto per chessboard.js) -->
  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
  <!-- chess.js per la logica degli scacchi -->
  <script src="https://cdnjs.cloudflare.com/ajax/libs/chess.js/0.12.0/chess.min.js"></script>
  <!-- chessboard.js per la scacchiera -->
  <script src="https://cdnjs.cloudflare.com/ajax/libs/chessboard-js/1.0.0/chessboard-1.0.0.min.js"></script>
  <!-- Bootstrap JS -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
  
  <!-- MAIN SCRIPT: Gestione partita, orologi, undo, indicatori di turno, ecc. -->
  <script>
    ////////////////////////////////
    // Variabili Globali e Setup
    ////////////////////////////////
    let game = new Chess();
    let board = null;
    let capturedWhite = []; // Pezzi bianchi catturati (persi da Bianco, quindi presi dal Bot)
    let capturedBlack = []; // Pezzi neri catturati (persi dal Bot, quindi presi da Bianco)
    const pieceValues = { p: 1, n: 3, b: 3, r: 5, q: 9 };
    const pieceSymbols = {
      p: { white: "♟︎", black: "♙" },
      n: { white: "♞", black: "♘" },
      b: { white: "♝", black: "♗" },
      r: { white: "♜", black: "♖" },
      q: { white: "♛", black: "♕" }
    };
    
    // Orologi: defaultTime è letto dal dropdown in secondi (default 300 = 5 minuti)
    let defaultTime = parseInt(document.getElementById("gameTime").value);
    let whiteTime = defaultTime;
    let blackTime = defaultTime;
    let clockInterval = null;
    
    // Colore del giocatore, letto dal dropdown (default "w")
    let playerColor = document.getElementById("playerColor").value;
    
    ////////////////////////////////
    // Funzioni Orologio (Clocks)
    ////////////////////////////////
    function updateClockDisplay() {
      function formatTime(s) {
        let m = Math.floor(s / 60), sec = s % 60;
        return (m < 10 ? "0" + m : m) + ":" + (sec < 10 ? "0" + sec : sec);
      }
      $("#clockWhite").text("Bianco: " + formatTime(whiteTime));
      $("#clockBlack").text("Nero: " + formatTime(blackTime));
    }
    
    function startClock() {
      stopClock();
      clockInterval = setInterval(() => {
        if (game.game_over()) {
          stopClock();
          return;
        }
        if (game.turn() === "w") {
          whiteTime--;
          if (whiteTime < 0) {
            stopClock();
            alert("Tempo esaurito per Bianco. Bot vince!");
            return;
          }
        } else {
          blackTime--;
          if (blackTime < 0) {
            stopClock();
            alert("Tempo esaurito per Nero. Tu vinci!");
            return;
          }
        }
        updateClockDisplay();
      }, 1000);
    }
    
    function stopClock() {
      if (clockInterval) clearInterval(clockInterval);
      clockInterval = null;
    }
    
    function resetClocks() {
      whiteTime = defaultTime;
      blackTime = defaultTime;
      updateClockDisplay();
    }
    
    ////////////////////////////////
    // Funzione per determinare il vincitore
    ////////////////////////////////
    function getWinnerName() {
      // Quando c'è checkmate, game.turn() è il colore perdente. Quindi il vincitore è l'opposto.
      const winningColor = (game.turn() === "w") ? "b" : "w";
      return (winningColor === playerColor) ? "Tu" : "Bot";
    }
    
    ////////////////////////////////
    // Aggiornamenti: Move History, Captures, Stato
    ////////////////////////////////
    function updateMoveHistory() {
      let history = game.history({ verbose: true });
      let historyHtml = "<table class='table table-bordered'><thead><tr><th>Mossa</th><th>Bianco</th><th>Bot</th></tr></thead><tbody>";
      for (let i = 0; i < history.length; i += 2) {
        let moveNumber = (i / 2) + 1;
        let whiteMoveObj = history[i];
        let blackMoveObj = history[i + 1];
        let whiteMove = whiteMoveObj
          ? whiteMoveObj.piece.toUpperCase() + whiteMoveObj.from + (whiteMoveObj.captured ? "x" : "-") + whiteMoveObj.to
          : "";
        let blackMove = blackMoveObj
          ? blackMoveObj.piece.toUpperCase() + blackMoveObj.from + (blackMoveObj.captured ? "x" : "-") + blackMoveObj.to
          : "";
        historyHtml += `<tr>
                          <td>${moveNumber}</td>
                          <td>${whiteMove}</td>
                          <td>${blackMove}</td>
                        </tr>`;
      }
      historyHtml += "</tbody></table>";
      $("#moveHistory").html(historyHtml);
    }
    
    function updateCapturedPieces() {
      let whiteCapsHTML = "";
      let blackCapsHTML = "";
      let whiteScore = 0;
      let blackScore = 0;
      
      capturedWhite.forEach(piece => {
        if(pieceSymbols[piece]){
          whiteCapsHTML += pieceSymbols[piece]["white"] + " ";
          whiteScore += pieceValues[piece];
        } else {
          whiteCapsHTML += piece + " ";
        }
      });
      capturedBlack.forEach(piece => {
        if(pieceSymbols[piece]){
          blackCapsHTML += pieceSymbols[piece]["black"] + " ";
          blackScore += pieceValues[piece];
        } else {
          blackCapsHTML += piece + " ";
        }
      });
      
      $("#capturedPieces").html(
        "<strong>Bianco ha catturato:</strong> " + blackCapsHTML + " (Totale: " + blackScore + ")<br>" +
        "<strong>Bot ha catturato:</strong> " + whiteCapsHTML + " (Totale: " + whiteScore + ")"
      );
    }
    
    function updateStatus() {
      let statusText = "";
      if (game.turn() === "w") {
        statusText = "Turno: Bianco";
      } else {
        statusText = "Turno: Bot (Nero)";
      }
      if (game.in_check()){
        statusText += " – Scacco al Re!";
      }
      $("#gameStatus").text(statusText);
    }
    
    ////////////////////////////////
    // Funzioni per evidenziare le mosse legali
    ////////////////////////////////
    function removeHighlights() {
      $('#board .square-55d63').css('background', '');
    }
    function highlightSquare(square) {
      let $square = $('#board .square-' + square);
      let background = $square.hasClass("black-3c85d") ? "#696969" : "#a9a9a9";
      $square.css("background", background);
    }
    
    ////////////////////////////////
    // Gestione Drag & Drop (turno del giocatore)
    ////////////////////////////////
    function onDragStart(source, piece, position, orientation) {
      // Il giocatore può muovere solo se è il suo turno
      if (game.game_over() || (game.turn() !== playerColor)) return false;
      removeHighlights();
      updateStatus();
    }
    
    function onDragMove(e, source, piece, position, orientation) {
      removeHighlights();
      let moves = game.moves({ square: source, verbose: true });
      if (moves.length === 0) return;
      moves.forEach(m => highlightSquare(m.to));
    }
    
    function onDrop(source, target) {
      removeHighlights();
      if (game.turn() !== playerColor) return "snapback";
      let move = game.move({
        from: source,
        to: target,
        promotion: "q"
      });
      if (move === null) return "snapback";
      
      board.position(game.fen());
      
      if (move.captured) {
        capturedBlack.push(move.captured);
        updateCapturedPieces();
      }
      
      updateMoveHistory();
      updateStatus();
      
      // Aggiorna orologio
      stopClock();
      startClock();
      
      // Se il turno passa al Bot, chiamare la mossa del bot
      if (!game.game_over() && game.turn() !== playerColor) {
        setTimeout(() => { makeBotMove(); }, 500);
      }
      
      if (game.game_over()){
        let msg = "Partita terminata: ";
        if (game.in_checkmate()) {
          msg += getWinnerName() + " ha vinto per scacco mate!";
        } else {
          msg += "Patta!";
        }
        setTimeout(() => { alert(msg); }, 200);
      }
    }
    
    function onSnapEnd() {
      board.position(game.fen());
      updateStatus();
    }
    
    ////////////////////////////////
    // Inizializza la scacchiera
    ////////////////////////////////
    function initBoard() {
      // Leggi il colore scelto
      playerColor = $("#playerColor").val();
      board = Chessboard("board", {
        draggable: true,
        dropOffBoard: "snap",
        sparePieces: false,
        position: "start",
        orientation: (playerColor === "w" ? "white" : "black"),
        pieceTheme: "https://chessboardjs.com/img/chesspieces/wikipedia/{piece}.png",
        onDragStart: onDragStart,
        onDragMove: onDragMove,
        onDrop: onDrop,
        onSnapEnd: onSnapEnd
      });
      // Se il giocatore ha scelto Nero, il bot deve muovere per primo
      if (game.turn() !== playerColor) {
        setTimeout(() => { makeBotMove(); }, 500);
      }
    }
    
    ////////////////////////////////
    // Undo Mossa
    ////////////////////////////////
    function undoMove() {
      if (game.history().length === 0) return;
      game.undo();
      if (game.turn() !== playerColor && game.history().length > 0) {
        game.undo();
      }
      board.position(game.fen());
      updateMoveHistory();
      updateStatus();
      resetClocks();
    }
    
    ////////////////////////////////
    // Reset Partita
    ////////////////////////////////
    function resetGame() {
      game.reset();
      playerColor = $("#playerColor").val();
      resetClocks();
      capturedWhite = [];
      capturedBlack = [];
      updateCapturedPieces();
      updateMoveHistory();
      updateStatus();
      board.start();
      board.orientation((playerColor === "w" ? "white" : "black"));
      if (game.turn() !== playerColor) {
        setTimeout(() => { makeBotMove(); }, 500);
      }
      stopClock();
      startClock();
    }
    
    ////////////////////////////////
    // Inizializzazione degli orologi
    ////////////////////////////////
    function initClocks() {
      // Leggi il tempo scelto dal dropdown
      defaultTime = parseInt(document.getElementById("gameTime").value);
      whiteTime = defaultTime;
      blackTime = defaultTime;
      updateClockDisplay();
      startClock();
    }
    
    ////////////////////////////////
    // Avvio: Inizializza board, orologi e listener
    ////////////////////////////////
    $(document).ready(function() {
      playerColor = $("#playerColor").val();
      initBoard();
      initClocks();
    });
    
    // Listener per i controlli
    document.getElementById("resetGame").addEventListener("click", resetGame);
    document.getElementById("undoMove").addEventListener("click", undoMove);
    document.getElementById("gameTime").addEventListener("change", function() {
      defaultTime = parseInt(this.value);
      console.log("Tempo di partita aggiornato a:", defaultTime, "secondi");
      resetClocks();
    });
    document.getElementById("playerColor").addEventListener("change", function() {
      playerColor = this.value;
      // Ricrea la scacchiera con l'orientamento aggiornato
      board.orientation((playerColor === "w" ? "white" : "black"));
    });
    
    ////////////////////////////////
    // Funzione per l'orologio: aggiornamento display e avvio/stop
    ////////////////////////////////
    function updateClockDisplay() {
      function formatTime(s) {
        let m = Math.floor(s / 60), sec = s % 60;
        return (m < 10 ? "0" + m : m) + ":" + (sec < 10 ? "0" + sec : sec);
      }
      $("#clockWhite").text("Bianco: " + formatTime(whiteTime));
      $("#clockBlack").text("Nero: " + formatTime(blackTime));
    }
    
    function startClock() {
      stopClock();
      clockInterval = setInterval(() => {
        if (game.game_over()) {
          stopClock();
          return;
        }
        if (game.turn() === "w") {
          whiteTime--;
          if (whiteTime < 0) {
            stopClock();
            alert("Tempo esaurito per Bianco. Bot vince!");
            return;
          }
        } else {
          blackTime--;
          if (blackTime < 0) {
            stopClock();
            alert("Tempo esaurito per Nero. Tu vinci!");
            return;
          }
        }
        updateClockDisplay();
      }, 1000);
    }
    
    function stopClock() {
      if (clockInterval) clearInterval(clockInterval);
      clockInterval = null;
    }
    
    function resetClocks() {
      whiteTime = defaultTime;
      blackTime = defaultTime;
      updateClockDisplay();
    }
    
    ////////////////////////////////
    // Funzione per determinare il vincitore
    ////////////////////////////////
    function getWinnerName() {
      const winningColor = (game.turn() === "w" ? "b" : "w");
      return (winningColor === playerColor) ? "Tu" : "Bot";
    }
  </script>
  
  <!-- Includi il file bot.js -->
  <script src="bot.js"></script>
  
</body>
</html>
