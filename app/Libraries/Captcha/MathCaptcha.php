<?php

namespace App\Libraries\Captcha;


class MathCaptcha
{

    protected $numOne;
    protected $numTwo;
    protected $total;

    protected $img;
    protected $imgWidth = 75;
    protected $imgHeight = 38;

    protected $background;
    protected $textColor;

    protected $identity = 'math_captcha';

    private $session;

    public function __construct()
    {
        $this->session = service( 'session' );
    }


    public function generate(): MathCaptcha
    {
        $this->initNumbers()
             ->createImg()
             ->save();

        return $this;

    }

    public function display()
    {
        header( 'Content-type: image/png' );

        imagepng( $this->img );

        //destroys used resources
        imagecolordeallocate( $this->img, $this->textColor );
        imagecolordeallocate( $this->img, $this->background );
        imagedestroy( $this->img );

    }

    protected function createImg(): MathCaptcha
    {
        $text = $this->numOne . ' + ' . $this->numTwo . ' =';

        $this->img = imagecreate($this->imgWidth, $this->imgHeight);
        $this->background = imagecolorallocate( $this->img, 25, 28, 32 );
        $this->textColor = imagecolorallocate( $this->img, 255, 255, 255 );
        $font = __DIR__ . '/font/verdana.ttf';

        imagettftext($this->img, 16, 0, 0, 26, $this->textColor, $font, $text);

        return $this;

    }


    protected function initNumbers(): MathCaptcha
    {
        $this->numOne = rand(1, 9);
        $this->numTwo = rand(1, 9);

        $this->total = $this->numOne + $this->numTwo;

        return $this;

    }

    public function setIdentity( $identity )
    {
        $this->identity = $identity;
    }

    public function setImgWidth( $width )
    {
        $this->imgWidth = $width;
    }

    public function setImgHeight( $height )
    {
        $this->imgHeight = $height;
    }

    public function isValid( $resp ): bool
    {
        if($this->session->has( $this->identity )){

            if($this->session->get( $this->identity ) == $resp){

                return true;

            }

        }

        return false;

    }

    public function destroy()
    {
        $this->session->remove( $this->identity );
    }

    public function updateCaptcha()
    {

    }

    private function save()
    {
        $this->session->set( $this->identity, $this->total );
    }

}