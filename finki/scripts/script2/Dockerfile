FROM python:3.7.4-buster

RUN echo "deb http://ftp.uk.debian.org/debian/ bullseye main contrib non-free" >> /etc/apt/sources.list
RUN echo "deb-src http://ftp.uk.debian.org/debian/ bullseye main contrib non-free" >> /etc/apt/sources.list
RUN echo "deb http://apt.llvm.org/buster/ llvm-toolchain-buster-9 main" >> /etc/apt/sources.list
RUN echo "deb-src http://apt.llvm.org/buster/ llvm-toolchain-buster-9 main" >> /etc/apt/sources.list
RUN apt-key adv --keyserver hkp://keys.gnupg.net --recv-keys 15CF4D18AF4F7421
RUN apt update
RUN apt install gcc -y
RUN apt install libllvm-9-ocaml-dev libllvm9 llvm-9 llvm-9-dev llvm-9-doc llvm-9-examples llvm-9-runtime -y
RUN apt install clang-9 clang-tools-9 clang-9-doc libclang-common-9-dev libclang-9-dev libclang1-9 clang-format-9 python-clang-9 clangd-9 -y
RUN ln -s /usr/lib/llvm-9/lib/libclang.so.1 /usr/lib/libclang.so

RUN pip install --upgrade pip
RUN pip install clang-5

ADD codeprocessor.py /
RUN chmod 777 ./codeprocessor.py

ADD temp7.txt /
ADD temp.txt /


CMD ["python", "./codeprocessor.py"]



