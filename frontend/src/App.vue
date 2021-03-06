<template>
    <div id="app">
        <div v-if="loadingCount > 0" v-cloak id="loader"></div>

        <div v-if="messageTxt" v-cloak class="alert fade show app-alert" :class="[messageType]">
            {{ messageTxt }}
            <button type="button" class="close" v-on:click="messageTxt = ''" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>

        <nav-bar v-if="settings.customization_default_theme" v-cloak
                 :auth-char="authChar" :page="page" :settings="settings"></nav-bar>

        <characters ref="charactersModal"></characters>

        <component v-if="settings.customization_default_theme" v-cloak v-bind:is="page"
                   :route="route"
                   :settings="settings"
                   :player="player"
                   :auth-char="authChar">
        </component>

        <footer v-if="settings.customization_github" v-cloak class="footer border-top text-muted small">
            <div class="container-fluid">
                <span v-cloak>{{ settings.customization_footer_text }}</span>
                <a v-cloak :href="settings.customization_github" class="github text-dark text-muted"
                   target="_blank" rel="noopener noreferrer"
                   title="Neucore on GitHub"><span class="fab fa-github"></span></a>
            </div>
            <div class="container-fluid small">
                "EVE", "EVE Online", "CCP" and all related logos and images are trademarks or registered trademarks of
                <a href="http://www.ccpgames.com/" target="_blank" rel="noopener noreferrer">CCP hf</a>.
            </div>
        </footer>
    </div>
</template>

<script>
import { ApiClient, AuthApi, CharacterApi, PlayerApi, SettingsApi } from 'neucore-js-client';
import superAgentPlugin from './superagent-plugin.js';
import NavBar from './components/NavBar.vue';
import Characters from './components/Characters.vue';
import Home from './pages/Home.vue';
import Groups from './pages/Groups.vue';
import GroupManagement from './pages/GroupManagement.vue';
import AppManagement from './pages/AppManagement.vue';
import PlayerGroupManagement from './pages/PlayerGroupManagement.vue';
import GroupAdmin from './pages/GroupAdmin.vue';
import AppAdmin from './pages/AppAdmin.vue';
import UserAdmin from './pages/UserAdmin.vue';
import TrackingAdmin from './pages/TrackingAdmin.vue';
import Esi from './pages/Esi.vue';
import SystemSettings from './pages/SystemSettings.vue';
import Tracking from './pages/Tracking.vue';
import Watchlist from './pages/Watchlist.vue';
import WatchlistAdmin from './pages/WatchlistAdmin.vue';
import FindAlts from './pages/FindAlts.vue';

export default {
    name: 'app',

    components: {
        NavBar,
        Characters,
        Home,
        Groups,
        GroupManagement,
        AppManagement,
        PlayerGroupManagement,
        GroupAdmin,
        AppAdmin,
        UserAdmin,
        TrackingAdmin,
        SystemSettings,
        Esi,
        Tracking,
        Watchlist,
        WatchlistAdmin,
        FindAlts,
    },

    props: {
        player: Object,
        settings: Object,
        loadingCount: Number,
    },

    data: function() {
        return {
            /**
             * Current route (hash splitted by /), first element is the current page.
             */
            route: [],

            /**
             * All available pages
             */
            pages: [
                'Home',
                'Groups',
                'GroupManagement',
                'AppManagement',
                'PlayerGroupManagement',
                'GroupAdmin',
                'AppAdmin',
                'UserAdmin',
                'TrackingAdmin',
                'Esi',
                'SystemSettings',
                'Tracking',
                'Watchlist',
                'WatchlistAdmin',
                'FindAlts',
            ],

            /**
             * Current page
             */
            page: null,

            /**
             * The authenticated character
             */
            authChar: null,

            messageTxt: '',

            messageType: '',
        }
    },

    created: function() {
        // configure neucore-js-client
        ApiClient.instance.basePath =
            `${window.location.protocol}//${window.location.hostname}:${window.location.port}/api`;
        ApiClient.instance.plugins = [superAgentPlugin(this)];

        // initial route
        this.updateRoute();

        // route listener
        window.addEventListener('hashchange', () => {
            this.updateRoute();
        });

        // event listeners
        this.$root.$on('playerChange', () => {
            this.getPlayer();
        });
        this.$root.$on('settingsChange', () => {
            this.getSettings();
        });
        this.$root.$on('message', (text, type, timeout) => {
            this.showMessage(text, type, timeout);
        });
        this.$root.$on('showCharacters', (playerId) => {
            this.$refs.charactersModal.showCharacters(playerId);
        });

        // refresh session every 5 minutes
        const vm = this;
        window.setInterval(function() {
            vm.getAuthenticatedCharacter(true);
        }, 1000*60*5);

        // get initial data
        this.getSettings();
        this.getAuthenticatedCharacter();
        this.getPlayer();
    },

    watch: {
        settings: function() {
            window.document.title = this.settings.customization_document_title;
        }
    },

    methods: {
        showMessage: function(text, type, timeout) {
            this.messageTxt = text;
            this.messageType = `alert-${type}`;
            if (timeout) {
                const vm = this;
                window.setTimeout(function() {
                    vm.messageTxt = '';
                }, timeout);
            }
        },

        updateRoute() {
            this.route = window.location.hash.substr(1).split('/');

            // handle routes that do not have a page
            const vm = this;
            if (this.route[0] === 'logout') {
                this.logout();
            } else if (['login', 'login-alt'].indexOf(this.route[0]) !== -1) {
                authResult();
                // Remove the hash value so that it does not appear in bookmarks, for example.
                window.location.hash = '';
            } else if (this.route[0] === 'login-director') {
                authResult('info');
                // can't redirect to settings here because player may not be loaded yet for role check
            }  else if (this.route[0] === 'login-mail') {
                window.location.hash = 'SystemSettings/Mails';
            }

            // set page, fallback to Home
            if (this.pages.indexOf(this.route[0]) === -1) {
                this.route[0] = 'Home';
            }
            this.page = this.route[0];

            /**
             * @param {string} [successMessageType]
             */
            function authResult(successMessageType) {
                new AuthApi().result(function(error, data) {
                    if (error) {
                        window.console.error(error);
                        return;
                    }
                    if (data.success) {
                        if (successMessageType) {
                            vm.message(data.message, successMessageType);
                        }
                    } else {
                        vm.message(data.message, 'error');
                    }
                });
            }
        },

        getSettings: function() {
            const vm = this;
            new SettingsApi().systemList(function(error, data) {
                if (error) {
                    return;
                }
                const settings = {};
                for (const variable of data) {
                    settings[variable.name] = variable.value;
                }
                vm.$root.settings = settings; // watch() will work this way
            });
        },

        getAuthenticatedCharacter: function(ping) {
            const vm = this;
            new CharacterApi().show(function(error, data) {
                if (error) { // 403 usually
                    vm.authChar = null;
                    vm.$root.player = null;
                    vm.page = 'Home';
                } else if (! ping) { // don't update because it triggers watch events
                    vm.authChar = data;
                }
            });
        },

        getPlayer: function() {
            const vm = this;
            new PlayerApi().show(function(error, data) {
                if (error) { // 403 usually
                    vm.$root.player = null;
                    return;
                }
                vm.$root.player = data;

                // redirect to settings after director login if user has the settings role
                if (window.location.hash === '#login-director' && vm.hasRole('settings')) {
                    window.location.hash = 'SystemSettings/Directors';
                }
            });
        },

        logout: function() {
            const vm = this;
            new AuthApi().logout(function(error) {
                if (error) { // 403 usually
                    return;
                }
                vm.authChar = null;
                vm.$root.player = null;
            });
        },
    },
}
</script>

<style scoped>
    .alert.app-alert {
        position: fixed;
        top: 60px;
        left: 25%;
        right: 25%;
        z-index: 5000;
    }

    #loader {
        position: fixed;
        top: 25px;
        left: 50%;
        width: 120px;
        height: 120px;
        margin-left: -60px;
        z-index: 5000;
        border: 16px solid #555;
        border-top: 16px solid #eee;
        border-radius: 50%;
        animation: spin 2s linear infinite;
    }
    @keyframes spin {
        0% { transform: rotate(0deg); }
        100% { transform: rotate(360deg); }
    }

    .footer {
        padding: 5px 0;
        position: absolute;
        bottom: 0;
        width: 100%;
        max-height: 75px;
        overflow-y: auto;
    }
    .footer .container-fluid {
        text-align: center;
    }
    .footer .github {
        float: right;
    }
</style>
