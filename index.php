varios usuarios a la vez, mandar al tablero no solo la posicion de la mosca, tambien el id del usuario que lo hace
usuario tiene rol jugador y administrador
el jugador puede jugar
el administrador puede acceder al crud de usuarios y hacer todo lo del CRUD

tabla de usuarios y tabla de roles con jugador o administrador
tabla intermedia de roles asignados con el usuario y su rol


nada mas registro se es jugador, como jugador solo un tablero activo, sin tablero no se puede jugar, si se puede jugar solo se puede jugar en el tablero activo, si ya tiene no puede crearlo

hay que comprobar que cada jugador, solo puede jugar su tablero y no puede suplantar a otro usuario

si se olvida la contraseña, POST /olvido y que se genere una contraseña nueva de manera aleatoria