Esse projeto possibilita criar um MAKEFILE � partir de um SOLUCAO do Visual Studio.

Para compilar utilize:

  csc sln2make.cs

Em seguida para gerar o projeto MAKEFILE

  sln2make.exe SOLUCAO.SLN > Makefile

E para gerar o projeto no modo DEBUG e no diret�rio DIR:

  nmake TARGET=DIR 

ou, para gerar o projeto no modo RELEASE e no diretorio DIR:

  nmake TARGET=DIR RELEASE=

