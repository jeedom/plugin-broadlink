touch /tmp/dependancy_broadlink_in_progress
echo 0 > /tmp/dependancy_broadlink_in_progress
echo "********************************************************"
echo "*             Installation des dépendances             *"
echo "********************************************************"
apt-get update
echo 50 > /tmp/dependancy_broadlink_in_progress
pip install broadlink
echo 100 > /tmp/dependancy_broadlink_in_progress
echo "********************************************************"
echo "*             Installation terminée                    *"
echo "********************************************************"
rm /tmp/dependancy_broadlink_in_progress