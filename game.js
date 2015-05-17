var app = require('express')();
var http = require('http').Server(app);
var io = require('socket.io')(http);
//var jquery = require('jquery')();
//var crypto = require("crypto");
//var sha1 = crypto.createHash('sha1');

var users = {};
var games = {};
var soldados = {};
var turn = {};

//Configuracion de juego
var max_soldiers = 5;

app.get('/', function(req, res){
  res.sendfile('game.html');
});

io.on('connection', function(socket){
  var __id = socket.id;
  //users.push(__id);
  users[__id] = {nombre: "sin nombre", game: 0};
  console.log('Un jugador nuevo Conectado:'+socket.id);
  io.emit('online', users);
//  io.emit('jid', __id);
  //Nombres
  socket.on('nombres', function(data) {
    users[socket.id].nombre = data.name;
    //console.log(users);
    io.emit('online', users);
  });

  socket.on('reto', function(data) {
    //users[socket.id] = {nombre: data.name};
    console.log(data);
    io.sockets.connected[data.reto].emit('retador', {retador: data.reto, msj: 'El usuario ' + data.name + ' Te esta retando...', name: data.name, jid: __id});
    //console.log(io.eio);
    //io.emit('retador', io);
  });

  socket.on('aceptar', function(data) {
    var retado = __id;
    var retador = data.retador;

    console.log("Se ha aceptado un reto entre: " + retado + " VS " + retador);

    //creando SHA1 de juego nuevo
    //sha1.update(retado);
    var juego_nuevo = retado+retador//sha1.digest("hex");

    games[juego_nuevo] = {
      gid: juego_nuevo,
      batalla: users[retado].nombre + " VS " + users[retador].nombre,
      users: {}
    };
    games[juego_nuevo]['users'][retado] = {
        name: users[retado].nombre,
        jid: retado,
        puntos: 0,
        listo: 0,
        soldados: 0,
        turn: 1
      };
    games[juego_nuevo]['users'][retador] = {
        name: users[retador].nombre,
        jid: retador,
        puntos: 0,
        listo: 0,
        soldados: 0,
        turn: 0
      };
    //Incorporando soldados 0 defecto
    //soldados[juego_nuevo][retado] = [];
    //soldados[juego_nuevo][retador] = [];
    soldados[juego_nuevo] = {};
    soldados[juego_nuevo][retado] = [];
    soldados[juego_nuevo][retador] = [];

    //console.log(soldados[juego_nuevo]);
    
    //Enviando id de batalla 
    io.sockets.connected[retado].emit('nuevo_juego', games[juego_nuevo]);
    io.sockets.connected[retador].emit('nuevo_juego', games[juego_nuevo]);

    //Cambiando estado
    users[retado].game = 1;
    users[retador].game = 1;

    //Enviando nuevo estado a todos
    io.emit('online', users);
  });

//Agregar Soldados
  socket.on('add_soldier', function(data) {
    var game = data.game;
    if (games[game]['users'][__id].listo == 0) {
      if (soldados[game][__id].length <= max_soldiers) {
        soldados[game][__id].push(data.cord);
        io.sockets.connected[__id].emit('add_soldier', {status: 1, cord: data.cord});

        if (soldados[game][__id].length == max_soldiers) {
          games[game]['users'][__id].listo = 1;
          //Agregando cantidad maxima
          games[game]['users'][__id].soldados = soldados[game][__id].length;

          io.sockets.connected[__id].emit('add_soldier', {status: 0});
          //Enviando Status
          io.sockets.connected[__id].emit('status', games[game]);
          io.sockets.connected[data.oponente].emit('status', games[game]);
        }
      } else {
        io.sockets.connected[__id].emit('add_soldier', {status: 0});
      }
    }
    //console.log(soldados[game][__id]);
    //console.log(soldados[game][__id].length);
    //console.log(data);
  });

//Disparos
  socket.on('shoting', function(data) {
    var game = data.game;
    var oponente = data.oponente;
    //console.log(games[game]['users'][oponente].listo);
    if (games[game]['users'][oponente].listo == 1) {
      if(games[game]['users'][__id].turn == 1) {
        if(shot(game, oponente, data.cord))
        {
          io.sockets.connected[__id].emit('shoting', {status: 1, cord: data.cord, css: 'dead'});
          io.sockets.connected[oponente].emit('shoting', {status: 1, cord: data.cord, css: 'dead_reciv'});

          //Chekando Ganador
          if (games[game]['users'][oponente].soldados < 1) {
            io.sockets.connected[__id].emit('shoting', {status: 0,msj: "Eres el Ganador :D !!"});
            io.sockets.connected[oponente].emit('shoting', {status: 0,msj: "Has perdido... buhhhh !!! :("});
          }

          //Enviando Status
          io.sockets.connected[__id].emit('status', games[game]);
          io.sockets.connected[data.oponente].emit('status', games[game]);
        } else {
          io.sockets.connected[__id].emit('shoting', {status: 1, cord: data.cord, css: 'shot'});
          io.sockets.connected[oponente].emit('shoting', {status: 1, cord: data.cord, css: 'shot_reciv'});
        }

        games[game]['users'][__id].turn = 0;
        games[game]['users'][oponente].turn = 1;

        io.sockets.connected[__id].emit('turn', {status: 0});
        io.sockets.connected[oponente].emit('turn', {status: 1});
      } else {
        io.sockets.connected[__id].emit('shoting', {status: 0,msj: "Espera que tu oponente dispare, no es tu turno"});
      }
    } else {
      io.sockets.connected[__id].emit('shoting', {status: 0,msj: "Tu oponente Aún no está listo"});
    }

  });

  //Coordenadas
  socket.on('posicion', function(dt) {
    console.log(dt);
    io.emit('posicion', dt);
  });



  //console.log(users);
  socket.on('disconnect', function(){
    console.log('user disconnected');
    //users.splice(users.indexOf(socket.id), 1);
    delete users[__id];
    io.emit('online', users);
    //console.log(users);
  });


});

http.listen(3000, function(){
  console.log('listening on *:1234');
});

function extend(obj1,obj2){
  var obj3 = {}; 
  for (var attrname in obj1) { 
  obj3[attrname] = obj1[attrname];
  } 
  for (var attrname in obj2) { 
    obj3[attrname] = obj2[attrname];
  }
  return obj3; 
}

function shot(idgame, oponente, cord) {
  var detect = soldados[idgame][oponente].indexOf(cord);
  if(detect < 0) {
    return false;
  }
  soldados[idgame][oponente].splice(detect, 1);
  //delete soldados[idgame][oponente][cord];
  games[idgame]['users'][oponente].soldados = soldados[idgame][oponente].length;
  /*console.log(cord);
  console.log(soldados[idgame][oponente]);
  console.log("Quedan: "+soldados[idgame][oponente].length);*/
  return true;
}