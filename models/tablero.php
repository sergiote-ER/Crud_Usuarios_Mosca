<?php

class Tablero
{
    public $tamano;
    public $intentos;
    public $posMosca;
    public $tablero;

    public function __construct($tamano = 10, $intentos = 5)
    {
        $this->tamano = (int)$tamano;
        $this->intentos = (int)$intentos;
        
        // Rellenamos el tablero con ceros según el tamaño
        $this->tablero = array_fill(0, $this->tamano, 0);
        
        // Colocamos la mosca (valor 1) en una posición aleatoria
        $this->posMosca = rand(0, $this->tamano - 1);
        $this->tablero[$this->posMosca] = 1;
    }

    /**
     * Procesa un golpe en la casilla indicada:
     * Devuelve:
     *  1  -> Cazada
     *  2  -> Casi le das (casilla adyacente, la mosca revolotea y cambia de casilla)
     *  0  -> Ni se entera (fuera de los adyacentes, no te has acercado siquiera a la mosca)
     */
    public function darManotazo($pos)
    {
        $this->intentos--;

        // Cazada
        if ($pos === $this->posMosca) {
            return 1;
        }

        // Casi le das (distancia de 1 casilla a la izquierda o derecha)
        if (abs($pos - $this->posMosca) === 1) {
            $this->revolotear();
            return 2;
        }

        // Ni se ha enterado la mosca
        return 0;
    }

    /**
     * Mueve la mosca a una nueva casilla aleatoria dentro del tablero
     */
    public function revolotear()
    {
        // Limpiamos el tablero anterior
        $this->tablero = array_fill(0, $this->tamano, 0);
        
        // Generamos una nueva posición
        $this->posMosca = rand(0, $this->tamano - 1);
        $this->tablero[$this->posMosca] = 1;
    }
}
?>