Kohana-icecat
=============

This is a simple kohana-module for executing data from IceCat catalog. You can use it in your internet shop.

You needed  for user login and password before? try to get it on https://ua.icecat.biz/uk/menu/register/index.htm




Example:
    
    /*
     *  Get product Specification
     *  @param string EAN-code
     *  @param bool Draw description
     *  @param bool Draw picture
     * 
     *  @return product view/ FALSE
     */
     
    echo Icecat::instance()->getProductSpec("EAN-code", TRUE, TRUE);
