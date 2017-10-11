PROGRESS_FILE=/tmp/dependancy_broadlink_in_progress
if [ ! -z $1 ]; then
	PROGRESS_FILE=$1
fi
touch ${PROGRESS_FILE}
echo 0 > ${PROGRESS_FILE}
echo "********************************************************"
echo "*             Installation des dépendances             *"
echo "********************************************************"
apt-get update
apt-get install python-pip python-requests
echo 50 > ${PROGRESS_FILE}
sudo pip install pycrypto
sudo pip install pyudev
echo 100 > ${PROGRESS_FILE}
echo "********************************************************"
echo "*             Installation terminée                    *"
echo "********************************************************"
rm ${PROGRESS_FILE}
