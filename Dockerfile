############################################################
# Dockerfile to build CGE file sharing server
# # Define app's environment with a Dockerfile so it can be reproduced anywhere:
############################################################

FROM php:7.0-apache
# COPY phpconfig/php.ini /usr/local/etc/php/
# COPY src/ /var/www/html/

# File Author / Maintainer
MAINTAINER Jose Luis Bellod Cisneros

# Update the repository sources list
RUN apt-get install debian-archive-keyring
RUN apt-key update





RUN apt-get update && apt-get install -y \
    aufs-tools \
    automake \
    btrfs-tools \
    build-essential \
    curl \
    debian-archive-keyring \
    dpkg-sig \
    git \
    iptables \
    libapparmor-dev \
    libcap-dev \
    libmysqlclient-dev \
    libsqlite3-dev \
    lxc \
    mercurial \
    openssh-server \
    parallel \
    perl \
    reprepro \
    python-pip \
    && docker-php-ext-install mysqli

# Install services from Github (testing repositories)
# TODO Install deployment versions
#
# RUN wget ftp://ftp.vim.org/pub/vim/unix/vim-7.4.tar.bz2
# RUN tar xvf vim-7.4.tar.bz2
# RUN cd vim74
# RUN make

RUN mkdir /root/.ssh/
RUN ssh-keyscan bitbucket.org >> /root/.ssh/known_hosts
# RUN ssh-keyscan -H github.com >> /root/.ssh/known_hosts

# https://github.com/vim/vim.git
  # CGE uploader
# RUN mkdir /var/www/uploader
# RUN git clone -b production-service-uploader https://bitbucket.org/genomicepidemiology/cge-tools.git --single-branch /var/www/uploader
# RUN find /var/www/uploader -type f -print -exec chmod 664 {} \;
# RUN find /var/www/uploader -type d -print -exec chmod 775 {} \;
# RUN cp -R /var/www/uploader /var/www/html/
COPY php /var/www/php
COPY CGE /var/www/html
# RUN touch /var/www/html/batch/tools/server/uploader/error_log
# RUN chmod 777 /var/www/html/batch/tools/server/uploader/error_log
RUN mkdir -p /home/data2/secure-upload
RUN chmod -R 777 /home
# MySQL
# Create custom MySQL user
# COPY sql.dump /docker-entrypoint-initdb.d/
#
# COPY datadir /usr/src/
# WORKDIR /usr/src
# RUN bash /usr/src/import.sh

# WebServer
# COPY CGE /var/www/html
# COPY php /var/www/php
CMD ["apache2-foreground"]
