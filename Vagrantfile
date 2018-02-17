# -*- mode: ruby -*-
# vi: set ft=ruby :


# Only tested with Vagrant + libvirtd

Vagrant.configure("2") do |config|
	config.vm.hostname = "brvneucore"
	config.vm.box = "generic/ubuntu1710"

	config.vm.network "forwarded_port", guest: 443, host: 443

	config.vm.synced_folder "./", "/var/www/bravecore"
	config.vm.network :private_network, ip: "192.168.121.4"

	# run setup script as root
	config.vm.provision "shell", inline: <<-SHELL
		export DEBIAN_FRONTEND=noninteractive

		usermod -a -G www-data vagrant

		apt update
		apt install -y curl git

		# setup php + composer
		apt install -y php php-fpm php-mysql php-zip php-mbstring php-intl php-dom php-apcu php-curl

		php -r "copy('https://getcomposer.org/installer', '/tmp/composer-setup.php');"

		php /tmp/composer-setup.php --install-dir=/usr/local/bin --filename=composer

		# setup node
		apt install -y nodejs npm

		# install apache
		apt install apache2 -y

		# setup mysql
		apt install mariadb-server -y

		service mysql start

		mysql -e 'CREATE DATABASE IF NOT EXISTS core'
		# TODO should pass password in via env
		mysql -e "GRANT ALL PRIVILEGES ON core.* TO core@localhost IDENTIFIED BY 'braveineve'"

		cp /var/www/bravecore/apache2/010-bravecore.vagrant.conf /etc/apache2/sites-available/010-bravecore.conf

		a2enmod rewrite
		a2enmod ssl
		a2ensite default-ssl
		a2ensite 010-bravecore
		a2enmod proxy_fcgi setenvif
		a2enconf php7.1-fpm


		systemctl reload apache2

	SHELL

	# run the server as an unprivileged user
	config.vm.provision "up", type: "shell", run: "always", privileged: false, inline: <<-SHELL
		echo "starting server"

		cd /var/www/bravecore

		if [ ! -f backend/.env ]; then
			echo 'backend/.env not setup'
			exit
		fi

		# Install dependencies and build backend and frontend:
		./install.sh

		echo
		echo ------------------------------------
		echo -- server up at https://localhost --
		echo ------------------------------------
		echo For frontend rebuilding:
		echo you can either run npm run watch from the /frontend directory, or
		echo run it inside the vm: vagrant ssh -c 'cd /var/www/bravecore/frontend && npm run watch'

	SHELL
end