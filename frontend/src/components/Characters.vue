<!--
Modal window with all characters of one player.
 -->

<template>
    <div v-cloak v-if="selectedPlayer" class="modal fade" id="playerModal">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        {{ selectedPlayer.name }}
                    </h5>
                    <button type="button" class="close" data-dismiss="modal">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <ul class="list-group">
                    <li v-for="character in selectedPlayer.characters" class="list-group-item">
                        <div class="badges">
                            <span v-if="character.validToken" class="badge badge-success ml-1">Valid token</span>
                            <span v-if="! character.validToken" class="badge badge-danger ml-1">Invalid token</span>
                            <a class="badge badge-secondary ml-1"
                               :href="'https://evewho.com/pilot/' + character.name"
                               target="_blank">Eve Who</a>
                        </div>

                        <img :src="'https://image.eveonline.com/Character/' + character.id + '_32.jpg'">
                        {{ character.name }}
                        <span v-if="character.main" class="fas fa-star text-warning" title="Main"></span>
                        <div class="small">
                            <span class="text-muted">Corporation:</span>
                            <span v-if="character.corporation">
                                [{{ character.corporation.ticker }}]
                                {{ character.corporation.name }}
                            </span>
                            <br>
                            <span class="text-muted"> &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; Alliance:</span>
                            <span v-if="character.corporation && character.corporation.alliance">
                                [{{ character.corporation.alliance.ticker }}]
                                {{ character.corporation.alliance.name }}
                            </span>
                        </div>
                    </li>
                </ul>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
module.exports = {
    props: {
        swagger: Object,
    },

    data: function() {
        return {
            selectedPlayer: null,
        }
    },

    methods: {
        showCharacters: function(playerId) {
            var vm = this;
            vm.loading(true);
            new this.swagger.PlayerApi().characters(playerId, function(error, data) {
                vm.loading(false);
                if (error) {
                    return;
                }
                vm.selectedPlayer = data;
                window.setTimeout(function() {
                    window.jQuery('#playerModal').modal('show');
                }, 10);
            });
        },
    }
}
</script>

<style scoped>
    .badges {
        float: right;
        text-align: right;
    }
</style>