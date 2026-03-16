export const GET_POSTS = `
  query GetPosts($limit: Int) {
    myFeed(limit: $limit) {
      id
      userId
      caption
      visibility
      likesCount
      createdAt
      updatedAt
      images {
        id
        storageKey
        altText
        isPrimary
      }
    }
    postCounters(limit: $limit) {
      postId
      likesCount
      commentsCount
      sharesCount
    }
    userLike(limit: $limit) {
      id
    }
    me {
      id
      username
      displayName
    }
  }
`

export const GET_POST_BY_ID = GET_POSTS